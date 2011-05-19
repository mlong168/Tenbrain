<?php
class AuthController extends Zend_Controller_Action
{
    protected $session;
    public function init ()
    {
        $this->session = new Zend_Session_Namespace('Auth');
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
		        $authAdapter = new Zend_Auth_Adapter_DbTable($accounts->getAdapter(), 
		        'accounts');
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
                    $this->_redirect('console');
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
                $userdata['password'] = md5($data['password']);
                $userdata['ip'] = $ip;
                $userdata['email'] = $data['email'];
                $userdata['username'] = $data['username'];
                
                $accounts->insert($userdata);
                $this->_redirect('console');
            }
        }
    }
    public function logoutAction ()
    {
        $storage = new Zend_Auth_Storage_Session();
        $storage->clear();
        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();
        
        $this->_redirect('auth/login');
    }
    public function forgotAction ()
    {}
    public function profileAction ()
    {
        $auth = Zend_Auth::getInstance();
        if($auth->hasIdentity())
        {
        	echo "ONLINE";
        }
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
            	$auth->clearIdentity();
                $fb_info = file_get_contents($api_url . $access_token);
                $fb_info = Zend_Json_Decoder::decode($fb_info);
                //LOGIN
				$facebook = new Application_Model_DbTable_FacebookAccounts();

				if($userid = $facebook->get_user($fb_info["id"]))
				{
					// Check if user is not signed in on a3m
					if ( ! $auth->hasIdentity())
					{
						// LOGIN
						$accounts = new Application_Model_DbTable_Accounts();
				        $authAdapter = new Zend_Auth_Adapter_DbTable($accounts->getAdapter(), 
				        'accounts');
				        $authAdapter->setIdentityColumn('id')
				            ->setCredentialColumn('password')
				            ->setIdentity($userid)
				            ->setCredential("");
				        $result = $auth->authenticate($authAdapter);
		                if ($result->isValid()) {
		                    $storage = new Zend_Auth_Storage_Session();
		                    $storage->write($authAdapter->getResultRowObject());
		                    $this->_redirect('console');
		                } else {
		                    $this->view->errorMessage = 'Invalid username or password. Please try again';
		                }
					}
					$this->_redirect('console');
					//Link to account
				}
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
    	$auth = Zend_Auth::getInstance();
        $config = $this->getInvokeArg('bootstrap')->getOption('twitter');
        $consumer = new Zend_Oauth_Consumer($config);
        $access_token = $consumer->getAccessToken($this->_request->getQuery(), 
        unserialize($this->session->request_token));
        $twitter_service = new Zend_Service_Twitter(
        array('accessToken' => $access_token));
        // verify user's credentials with Twitter
        $response = $twitter_service->account->verifyCredentials();
        // LOGIN
    			$twitter = new Application_Model_DbTable_TwitterAccounts();
				if($userid = $twitter->get_user($response->id))
				{
					// Check if user is not signed in on a3m
					if ( ! $auth->hasIdentity())
					{
						// LOGIN
		                $auth = Zend_Auth::getInstance();
		                $accounts = new Application_Model_DbTable_Accounts();
				        $authAdapter = new Zend_Auth_Adapter_DbTable($accounts->getAdapter(), 
				        'accounts');
				        $authAdapter->setIdentityColumn('id')
				            ->setCredentialColumn('password')
				            ->setIdentity($userid)
				            ->setCredential("");
				        $result = $auth->authenticate($authAdapter);
		                if ($result->isValid()) {
		                    $storage = new Zend_Auth_Storage_Session();
		                    $storage->write($authAdapter->getResultRowObject());
		                    $this->_redirect('console');
		                } else {
		                    $this->view->errorMessage = 'Invalid username or password. Please try again';
		                }
					}
					$this->_redirect('console');
					//Link to account
				}
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
    	require_once "Auth/OpenID/AX.php";
    	$auth = Zend_Auth::getInstance();
        // create file storage area for OpenID data
        $store = new Auth_OpenID_FileStore('./oid_store');
        // create OpenID consumer
        $consumer = new Auth_OpenID_Consumer($store);

        if (! $auth) {
            die("ERROR: Please enter a valid OpenID.");
        }
    	// Get OpenID consumer object
		$consumer = new Auth_OpenID_Consumer($store);
		
		if ($this->getRequest()->getParam('janrain_nonce'))
		{
			// Complete authentication process using server response
			$response = $consumer->complete("http://ten.com/auth/openidconnect");
			
			// Check the response status
			if ($response->status == Auth_OpenID_SUCCESS) 
			{
				print_r($response->getDisplayIdentifier());
				//die;
				$openid = new Application_Model_DbTable_OpenIdAccounts();
				
				if ($userid = $openid->get_user($response->getDisplayIdentifier()))
				{
					if ( ! $auth->hasIdentity())
					{
						// LOGIN
		                $auth = Zend_Auth::getInstance();
		                $accounts = new Application_Model_DbTable_Accounts();
				        $authAdapter = new Zend_Auth_Adapter_DbTable($accounts->getAdapter(), 
				        'accounts');
				        $authAdapter->setIdentityColumn('id')
				            ->setCredentialColumn('password')
				            ->setIdentity($userid)
				            ->setCredential("");
				        $result = $auth->authenticate($authAdapter);
		                if ($result->isValid()) {
		                    $storage = new Zend_Auth_Storage_Session();
		                    $storage->write($authAdapter->getResultRowObject());
		                    $this->_redirect('console');
		                } else {
		                    $this->view->errorMessage = 'Invalid username or password. Please try again';
		                }
					}
					$this->_redirect('console');
					// LINK
				}
				// The user has not connect google
				else
				{
					// Check if user is signed in
					if ( ! $auth->hasIdentity())
					{
						$openid_google = array();
				
						if ($ax_args = Auth_OpenID_AX_FetchResponse::fromSuccessResponse($response))
						{
							$ax_args = $ax_args->data;
							if (isset($ax_args['http://axschema.org/namePerson/friendly'][0])) $openid_google['username'] = $ax_args['http://axschema.org/namePerson/friendly'][0];
							if (isset($ax_args['http://axschema.org/contact/email'][0])) $email = $ax_args['http://axschema.org/contact/email'][0];
							if (isset($ax_args['http://axschema.org/namePerson'][0])) $openid_google['fullname'] = $ax_args['http://axschema.org/namePerson'][0];
							if (isset($ax_args['http://axschema.org/birthDate'][0])) $openid_google['dateofbirth'] = $ax_args['http://axschema.org/birthDate'][0];
							if (isset($ax_args['http://axschema.org/person/gender'][0])) $openid_google['gender'] = $ax_args['http://axschema.org/person/gender'][0];
							if (isset($ax_args['http://axschema.org/contact/postalCode/home'][0])) $openid_google['postalcode'] = $ax_args['http://axschema.org/contact/postalCode/home'][0];
							if (isset($ax_args['http://axschema.org/contact/country/home'][0])) $openid_google['country'] = $ax_args['http://axschema.org/contact/country/home'][0];
							if (isset($ax_args['http://axschema.org/pref/language'][0])) $openid_google['language'] = $ax_args['http://axschema.org/pref/language'][0];
							if (isset($ax_args['http://axschema.org/pref/timezone'][0])) $openid_google['timezone'] = $ax_args['http://axschema.org/pref/timezone'][0];
							if (isset($ax_args['http://axschema.org/namePerson/first'][0])) $openid_google['firstname'] = $ax_args['http://axschema.org/namePerson/first'][0]; // google only
							if (isset($ax_args['http://axschema.org/namePerson/last'][0])) $openid_google['lastname'] = ' '.$ax_args['http://axschema.org/namePerson/last'][0]; // google only
						}
						
						// Store user's google data in session
						$this->session->user_data = array(
							array(
								'provider' => 'openid', 
								'provider_id' => $response->getDisplayIdentifier(),
								'email' => isset($email) ? $email : NULL
							), $openid_google);
						
						// Create account
						$this->_redirect('auth/connectcreate');
					}
					else
					{
						// Connect google
						//$this->account_openid_model->insert($response->getDisplayIdentifier(), $this->session->userdata('account_id'));
						//$this->session->set_flashdata('linked_info', sprintf(lang('linked_linked_with_your_account'), lang('connect_google')));
						$this->_redirect('console');
					}
				}
			}
			// Auth_OpenID_CANCEL or Auth_OpenID_FAILURE or anything else
			else
			{
				$auth->hasIdentity() ?
					$this->_redirect('console') :   //LINK
						$this->_redirect('auth/register');
			}
		}
        // Begin OpenID authentication process
		$auth_request = $consumer->begin("https://www.google.com/accounts/o8/id");
		
		// Create ax request (Attribute Exchange)
		$ax_request = new Auth_OpenID_AX_FetchRequest;
		$ax_request->add(Auth_OpenID_AX_AttrInfo::make('http://axschema.org/namePerson/friendly', 1, TRUE, 'username'));
		$ax_request->add(Auth_OpenID_AX_AttrInfo::make('http://axschema.org/contact/email', 1, TRUE, 'email'));
		$ax_request->add(Auth_OpenID_AX_AttrInfo::make('http://axschema.org/namePerson', 1, TRUE, 'fullname'));
		$ax_request->add(Auth_OpenID_AX_AttrInfo::make('http://axschema.org/birthDate', 1, TRUE, 'dateofbirth'));
		$ax_request->add(Auth_OpenID_AX_AttrInfo::make('http://axschema.org/person/gender', 1, TRUE, 'gender'));
		$ax_request->add(Auth_OpenID_AX_AttrInfo::make('http://axschema.org/contact/postalCode/home', 1, TRUE, 'postalcode'));
		$ax_request->add(Auth_OpenID_AX_AttrInfo::make('http://axschema.org/contact/country/home', 1, TRUE, 'country'));
		$ax_request->add(Auth_OpenID_AX_AttrInfo::make('http://axschema.org/pref/language', 1, TRUE, 'language'));
		$ax_request->add(Auth_OpenID_AX_AttrInfo::make('http://axschema.org/pref/timezone', 1, TRUE, 'timezone'));
		$ax_request->add(Auth_OpenID_AX_AttrInfo::make('http://axschema.org/namePerson/first', 1, TRUE, 'firstname')); // google only
		$ax_request->add(Auth_OpenID_AX_AttrInfo::make('http://axschema.org/namePerson/last', 1, TRUE, 'lastname')); // google only
		$auth_request->addExtension($ax_request);
		
		// Redirect to authorizate URL
		header("Location: ".$auth_request->redirectURL("http://ten.com/", "http://ten.com/auth/openidconnect"));
        die();
    }
    private function get_user_by_provider ($provider, $provider_id)
    {
        $providers["facebook"] = new Application_Model_DbTable_FacebookAccounts();
        $providers["twitter"] = new Application_Model_DbTable_TwitterAccounts();
        $providers["openid"] = new Application_Model_DbTable_OpenIdAccounts();
        return $providers[$provider]->get_user($provider_id);
    }
    public function facebookloginAction ()
    {
        die();
    }
    public function twitterloginAction ()
    {
        die();
    }
    public function connectcreateAction ()
    {
        $accounts = new Application_Model_DbTable_Accounts();
        $facebook = new Application_Model_DbTable_FacebookAccounts();
        $twitter = new Application_Model_DbTable_TwitterAccounts();
        $openid = new Application_Model_DbTable_OpenIdAccounts();
        
        if (! $this->session->user_data)
            $this->_redirect('auth/login');
        $form = new Application_View_Helper_Connect();
        $this->view->form = $form;
        $user_data = $this->session->user_data[0];
        $user = $this->get_user_by_provider($user_data["provider"], 
        $user_data["provider_id"]);
        if ($user)
            $this->_redirect('auth/' . $user_data["provider"] . 'login');
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
                    	$provider_data["account_id"] = $account_id;
                        $provider_data["openid"] = $user_data["provider_id"];
                        $openid->insert($provider_data);
                        break;
                    default:
                        $this->_redirect('auth/register');
                        break;
                }
                //Auth
                $auth = Zend_Auth::getInstance();
                $accounts = new Application_Model_DbTable_Accounts();
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
                    $this->_redirect('console');
                } else {
                    $this->view->errorMessage = 'Invalid username or password. Please try again';
                }
            }
        }
    }
}

