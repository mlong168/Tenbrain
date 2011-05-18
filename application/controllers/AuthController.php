<?php
class AuthController extends Zend_Controller_Action
{
    protected $session;
    public function init ()
    {
        $this->session = new Zend_Session_Namespace('Default');
    }
    public function indexAction ()
    {
        $this->_redirect('auth/login');
    }
    public function loginAction ()
    {
        $accounts = new Application_Model_DbTable_Accounts();
        $form = new Application_View_Helper_LoginForm();
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                $auth = Zend_Auth::getInstance();
                $authAdapter = new Zend_Auth_Adapter_DbTable(
                $accounts->getAdapter(), 'accounts');
                $authAdapter->setIdentityColumn('username')
                    ->setCredentialColumn('password')
                    ->setIdentity($data['username'])
                    ->setCredential(md5($data['password']));
                $result = $auth->authenticate($authAdapter);
                if ($result->isValid()) {
                    if ($data["remember"])
                        Zend_Session::rememberMe();
                    $storage = new Zend_Auth_Storage_Session();
                    $storage->write($authAdapter->getResultRowObject());
                    $this->_redirect('auth/profile');
                } else {
                    $this->view->errorMessage = 'Invalid username or password. Please try again';
                }
            }
        }
    }
    public function registerAction ()
    {
        $accounts = new Application_Model_DbTable_Accounts();
        $form = new Application_View_Helper_RegistrationForm();
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                if ($data['password'] != $data['confir_password']) {
                    $this->view->errorMessage = 'Password and confirm password dont \' match';
                    return;
                }
                if ($accounts->isUnique($data['username'])) {
                    $this->view->errorMessage = 'Name already taken. Please choose another one.';
                    return;
                }
                $ip = null;
                if (isset($_SERVER["REMOTE_ADDR"])) {
                    $ip = $_SERVER["REMOTE_ADDR"];
                } else 
                    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
                    } else 
                        if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                            $ip = $_SERVER["HTTP_CLIENT_IP"];
                        }
                $data['password'] = md5($data['password']);
                $data['ip'] = $ip;
                unset($data['confirm_password']);
                $accounts->insert($data);
                $this->_redirect('auth/login');
            }
        }
    }
    public function logoutAction ()
    {
        $storage = new Zend_Auth_Storage_Session();
        $storage->clear();
        $this->_redirect('auth/login');
    }
    public function forgotAction ()
    {}
    public function profileAction ()
    {
        $storage = new Zend_Auth_Storage_Session();
        $data = $storage->read();
        if (! $data) {
            $this->_redirect('auth/login');
        }
        $this->view->username = $data->username;
    }
    public function facebookconnectAction ()
    {
        $api_url = "https://graph.facebook.com/me?access_token=";
        $auth = Zend_Auth::getInstance();

        $bootstrap = $this->getInvokeArg('bootstrap');
        $array = $bootstrap->getOption('facebook');
        $adapter = new Zend_Auth_Adapter_Facebook($array);
        $token = $this->_getParam('code');
        if ($token) {
            $adapter->setToken($token);
            $result = $auth->authenticate($adapter);
            if ($result->isValid()) {
                $access_token = $auth->getIdentity();
                $fb_info = file_get_contents($api_url . $access_token);
                $fb_info = Zend_Json_Decoder::decode($fb_info);
                //$facebook = new Application_Model_DbTable_FacebookAccounts();
                //$user = $facebook->get_user_by_id($fb_info->id);
                //Session
                $this->session->user_data = array(
                array('provider' => 'facebook', 'provider_id' => $fb_info["id"]), 
                array('fullname' => $fb_info["name"], 
                'firstname' => $fb_info["first_name"], 
                'lastname' => $fb_info["last_name"], 
                'gender' => $fb_info["gender"], 
                'dateofbirth' => $fb_info["birthday"], 
                'picture' => 'http://graph.facebook.com/' . $fb_info["id"] .
                 '/picture'));
                $this->_redirect('auth/connectcreate');
            } else {
                print $result->getMessages();
            }
        } else {
            $adapter->redirect();
        }
    }
    public function twitterconnectAction ()
    {
        $config = $this->getInvokeArg('bootstrap')->getOption('twitter');
        $consumer = new Zend_Oauth_Consumer($config);
        $token = $consumer->getRequestToken();
        $this->session->request_token = serialize($token);
        $consumer->redirect();
    }
    public function twittercallbackAction ()
    {
        $config = $this->getInvokeArg('bootstrap')->getOption('twitter');
        $consumer = new Zend_Oauth_Consumer($config);
        $access_token = $consumer->getAccessToken($this->_request->getQuery(), 
        unserialize($this->session->request_token));
        $twitter = new Zend_Service_Twitter(
        array('accessToken' => $access_token));
        // verify user's credentials with Twitter
        $response = $twitter->account->verifyCredentials();
        if ($response) {
            $this->session->user_data = array(
            array('provider' => 'twitter', 'provider_id' => (int) $response->id, 
            'username' => (string) $response->screen_name, 
            'token' => (string) $access_token->oauth_token, 
            'secret' => (string) $access_token->oauth_token_secret), 
            array('fullname' => $access_token->name, 
            'picture' => $access_token->profile_image_url));
            //
            $this->_redirect('auth/connectcreate');
        }
        //die();
        die();
    }
    public function openidconnectAction ()
    {
	    $consumer = new Zend_OpenId_Consumer();
	    if (!$consumer->login("andrey.tsok@gmail.com")) {
	        echo "OpenID login failed.";
	    }
        die();
    }
    
    private function isUniqueProviderUser($provider,$provider_id)
    {
    	$providers["facebook"] = new Application_Model_DbTable_FacebookAccounts();
        $providers["twitter"] = new Application_Model_DbTable_TwitterAccounts();
        
        return $providers[$provider]->isUnique($provider_id);
    }
    
    public function facebookloginAction ()
    {
    	
    	die;
    }
    
	public function twitterloginAction ()
    {
    	
    	die;
    }
    
    public function connectcreateAction ()
    {
        $accounts = new Application_Model_DbTable_Accounts();
        $facebook = new Application_Model_DbTable_FacebookAccounts();
        $twitter = new Application_Model_DbTable_TwitterAccounts();
        
        if (! $this->session->user_data)
            $this->_redirect('auth/login');
        $form = new Application_View_Helper_Connect();
        $this->view->form = $form;
        $user_data = $this->session->user_data[0];
        $isUnique = $this->isUniqueProviderUser($user_data["provider"],$user_data["provider_id"]);
        if(!$isUnique)
        	$this->_redirect('auth/'.$user_data["provider"].'login');
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                unset($this->session->user_data);
                //Create user
                
                $data = $form->getValues();
                if ($accounts->isUnique($data['username'])) {
                    $this->view->errorMessage = 'Name already taken. Please choose another one.';
                    return;
                }
                $ip = null;
                if (isset($_SERVER["REMOTE_ADDR"])) {
                    $ip = $_SERVER["REMOTE_ADDR"];
                } else 
                    if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
                        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
                    } else 
                        if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                            $ip = $_SERVER["HTTP_CLIENT_IP"];
                        }
                $data['password'] = "";
                $data['ip'] = $ip;
                $account_id = $accounts->insert($data);
                //Add to auth provider table
                switch ($user_data["provider"]) {
                    case "facebook":
                        $provider_data["account_id"] = $account_id;
                        $provider_data["facebook_id"] = $user_data["provider_id"];
                        $facebook->insert($provider_data);
                        break;
                    case "twitter":
                        $provider_data["account_id"] = $account_id;
                        $provider_data["twitter_id"] = $user_data["provider_id"];
                        $provider_data["oauth_token"] = $user_data["token"];
                        $provider_data["oauth_token_secret"] = $user_data["secret"];
                        $twitter->insert($provider_data);
                        break;
                    case "openid":
                        //$twitter->insert($provider_data);
                        break;
                    default:
                    	$this->_redirect('auth/register');
                    	break;
                }
                //Auth
                $auth = Zend_Auth::getInstance();
                $authAdapter = new Zend_Auth_Adapter_DbTable(
                $accounts->getAdapter(), 'accounts');
                $authAdapter->setIdentityColumn('username')
                    ->setCredentialColumn('password')
                    ->setIdentity($data['username'])
                    ->setCredential("");
                $result = $auth->authenticate($authAdapter);
                if ($result->isValid()) {
                    $storage = new Zend_Auth_Storage_Session();
                    $storage->write($authAdapter->getResultRowObject());
                    $this->_redirect('auth/profile');
                } else {
                    $this->view->errorMessage = 'Invalid username or password. Please try again';
                }
            }
        }
    }
}

