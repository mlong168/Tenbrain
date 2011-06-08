<?php
class AccountController extends Zend_Controller_Action
{
    protected $session;
    public function init ()
    {
        $this->session = new Zend_Session_Namespace('Account');
    }
	
	private function get_user_ip()
	{
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
		return $ip;
	}
	
    public function indexAction ()
    {
        $this->_redirect('account/sign_in');
    }
	
	private function send_welcome_email($email)
	{
    	$settings = new Application_Model_DbTable_Settings();
		$mail = $settings->getSetting('email');
		$mail_subject = $settings->getSetting('email_subject');
		mail($email,$mail_subject,$mail);
	}
	
	private function send_forgot_email($email)
	{
    	$settings = new Application_Model_DbTable_Settings();
		$mail = $settings->getSetting('forgot');
		$mail_subject = $settings->getSetting('forgot_email_subject');
		mail($email,$mail_subject,$mail);
	}
	
	private function send_password_mail($email,$pass)
	{
		$settings = new Application_Model_DbTable_Settings();
		$mail = $settings->getSetting('new_pass');
		//insert pass
		$mail_subject = $settings->getSetting('new_pass_email_subject');
		mail($email,$mail_subject,$mail);
	}
	
    public function signInAction ()
    {
        $accounts = new Application_Model_DbTable_Accounts();
        $form = new Application_View_Helper_LoginForm();
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                $auth = Zend_Auth::getInstance();
		        $authAdapter = new ZendExt_Auth_Adapter_MultiColumnDbTable($accounts->getAdapter(), 
		        'accounts');
		        $authAdapter->setIdentityColumn('username')
		        	->setAlternativeIdentityColumn('email')
		            ->setCredentialColumn('password')
		            ->setIdentity($data['login'])
		            ->setCredential(md5($data['password']));
		        $result = $auth->authenticate($authAdapter);
                if ($result->isValid()) {
                    if ($data["remember"])
                        Zend_Session::rememberMe();
                    $storage = new Zend_Auth_Storage_Session();
                    $storage->write($authAdapter->getResultRowObject());
					
					$redirect = new Zend_Session_Namespace('sign_in_redirect');
					if(isset($redirect->url))
					{
						$url = $redirect->url;
						unset($redirect->url);
						$this->_redirect($url);
					}
                    $this->_redirect('console');
					
                } else {
                    $this->view->errorMessage = 'Invalid username or password. Please try again';
                }
            }
        }
    }
    public function signUpAction ()
    {		
        $accounts = new Application_Model_DbTable_Accounts();
        $form = new Application_View_Helper_RegistrationForm();
        $this->view->form = $form;
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $data = $form->getValues();
                if (!$accounts->isUnique($data['username']) || !$accounts->isUniqueEmail($data['email'])) {
                    $this->view->errorMessage = 'Name or Email already taken. Please choose another one.';
                    return;
                }

                $userdata['password'] = md5($data['password']);
                $userdata['ip'] = $this->get_user_ip();
                $userdata['email'] = $data['email'];
                $userdata['username'] = $data['username'];
                
                $accounts->insert($userdata);
				//$this->send_welcome_email($userdata['email']);
                // LOGIN
                $auth = Zend_Auth::getInstance();
                $authAdapter = new ZendExt_Auth_Adapter_MultiColumnDbTable(
                $accounts->getAdapter(), 'accounts');
                $authAdapter->setIdentityColumn('username')
                    ->setCredentialColumn('password')
                    ->setIdentity($userdata['username'])
                    ->setCredential($userdata['password']);
                $result = $auth->authenticate($authAdapter);
                if ($result->isValid()) {
                    $storage = new Zend_Auth_Storage_Session();
                    $storage->write($authAdapter->getResultRowObject());
                    $this->_redirect('console');
                }
                $this->_redirect('account/sign_in');
            }
        }
    }
    public function signOutAction ()
    {
        $storage = new Zend_Auth_Storage_Session();
        $storage->clear();
        $auth = Zend_Auth::getInstance();
        $auth->clearIdentity();
        
        $this->_redirect('account/sign_in');
    }
    public function profileAction ()
    {
        $auth = Zend_Auth::getInstance();
        if($auth->hasIdentity())
        {
        	echo "ONLINE";
        }
    }
    public function facebookConnectAction ()
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
					if ( ! $auth->hasIdentity())
					{
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
                $this->_redirect('account/connect_create');
            } else {
                print $result->getMessages();
            }
        } else {
            $adapter->redirect();
        }
    }

	public function twitterConnectAction ()
    {
        $config = $this->getInvokeArg('bootstrap')->getOption('twitter');

		$twitteroauth = new TwitterOAuth($config['consumerKey'], $config['consumerSecret']);
		
		$request_token = $twitteroauth->getRequestToken($config['callbackUrl']);
		$_SESSION['oauth_token'] = $request_token['oauth_token'];
		$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];

		if($twitteroauth->http_code==200){
			$url = $twitteroauth->getAuthorizeURL($request_token['oauth_token']);
			header('Location: '. $url);
		} else {
			die('Something wrong happened.');
		}
		die;
    }
	
    public function forgotPasswordAction ()
    {
    	$form = new Application_View_Helper_Forgot();
        $this->view->form = $form;
		
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
        		$accounts = new Application_Model_DbTable_Accounts();
				$secure_key = $accounts->generate_forgot_key($email);
				$this->send_forgot_email($email,$key);
				$this->view->message = "Please view email box";
			}
		}
    }
	
	public function generateNewPasswordAction ()
	{
		$data = $this->_request->getQuery();
		if(isset($data["email"]) && isset($data["secure_key"]))
		{
			$accounts = new Application_Model_DbTable_Accounts();
			$isValid = $accounts->isValidSecure($data);
			if($isValid)
			{
				$pass = $account->change_new_password($data["email"]);
				$this->send_password_mail($email, $pass);
				$this->view->message = "Password sended";
			}
			else
			{
				$this->view->message = "Invalide secure key";
			}
		}
			
	}
	
    public function twitterCallbackAction ()
    {
    	$auth = Zend_Auth::getInstance();
        $config = $this->getInvokeArg('bootstrap')->getOption('twitter');
        $twitteroauth = new TwitterOAuth($config['consumerKey'], $config['consumerSecret'], $_SESSION['oauth_token'], $_SESSION['oauth_token_secret']);
		$access_token = $twitteroauth->getAccessToken($_GET['oauth_verifier']);

		$_SESSION['access_token'] = $access_token;
		$user_info = $twitteroauth->get('account/verify_credentials');
        // LOGIN
		$twitter = new Application_Model_DbTable_TwitterAccounts();
		$userid = $twitter->get_user($user_info->id_str);
		if($userid)
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
		}
        if ($user_info) {
            $this->session->user_data = array(
            array('provider' => 'twitter', 'provider_id' => (int) $user_info['id_str'], 
            'username' => (string) $user_info['screen_name'], 
            'token' => (string) $access_token['oauth_token'], 
            'secret' => (string) $access_token['oauth_token_secret']), 
            array('fullname' => $access_token['name'], 
            'picture' => $access_token['profile_image_url']));
            $this->_redirect('account/connect_create');
        }
    }
    public function googleConnectAction ()
    {
    	require_once "Auth/OpenID/AX.php";
    	$auth = Zend_Auth::getInstance();
        $store = new Auth_OpenID_FileStore('./oid_store');
        $consumer = new Auth_OpenID_Consumer($store);

        if (! $auth) {
            die("ERROR: Please enter a valid OpenID.");
        }
		$consumer = new Auth_OpenID_Consumer($store);
		$domain = $_SERVER['SERVER_NAME'];
		
		if ($this->getRequest()->getParam('janrain_nonce'))
		{
			$response = $consumer->complete("http://".$domain."/account/google_connect");
			if ($response->status == Auth_OpenID_SUCCESS) 
			{
				print_r($response->getDisplayIdentifier());
				$openid = new Application_Model_DbTable_OpenIdAccounts();
				
				if ($userid = $openid->get_user($response->getDisplayIdentifier()))
				{
					if ( ! $auth->hasIdentity())
					{
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
				}
				else
				{
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
						
						$this->_redirect('account/connect_create');
					}
					else
					{
						$this->_redirect('console');
					}
				}
			}
			else
			{
				$auth->hasIdentity() ?
					$this->_redirect('console') :   //LINK
						$this->_redirect('account/sign_up');
			}
		}
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
		header("Location: ".$auth_request->redirectURL("http://".$domain."/", "http://".$domain."/account/google_connect"));
        die();
    }
    private function get_user_by_provider ($provider, $provider_id)
    {
        $providers["facebook"] = new Application_Model_DbTable_FacebookAccounts();
        $providers["twitter"] = new Application_Model_DbTable_TwitterAccounts();
        $providers["openid"] = new Application_Model_DbTable_OpenIdAccounts();
        return $providers[$provider]->get_user($provider_id);
    }
    public function connectCreateAction ()
    {
        $accounts = new Application_Model_DbTable_Accounts();
        $facebook = new Application_Model_DbTable_FacebookAccounts();
        $twitter = new Application_Model_DbTable_TwitterAccounts();
        $openid = new Application_Model_DbTable_OpenIdAccounts();
        
        if (! $this->session->user_data)
            $this->_redirect('account/sign_in');
        $form = new Application_View_Helper_Connect();
        $this->view->form = $form;
        $user_data = $this->session->user_data[0];
        $user = $this->get_user_by_provider($user_data["provider"], 
        $user_data["provider_id"]);
        if ($user)
            $this->_redirect('account/' . $user_data["provider"] . '_connect');
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                unset($this->session->user_data);
                //Create user
                $data = $form->getValues();
                if (!$accounts->isUnique($data['username']) || !$accounts->isUnique($data['email'])) {
                    $this->view->errorMessage = 'Name or Email already taken. Please choose another one.';
                    return;
                }

                $data['password'] = "";
                $data['ip'] = $this->get_user_ip();
                $account_id = $accounts->insert($data);
                //Add to auth provider table
                switch ($user_data["provider"]) {
                    case "facebook":
                        $provider_data["account_id"] = $account_id;
                        $provider_data["facebook_id"] = $user_data["provider_id"];
						$provider_data["linkedon"] = date();
                        $facebook->insert($provider_data);
                        break;
                    case "twitter":
                        $provider_data["account_id"] = $account_id;
                        $provider_data["twitter_id"] = $user_data["provider_id"];
                        $provider_data["oauth_token"] = $user_data["token"];
                        $provider_data["oauth_token_secret"] = $user_data["secret"];
						$provider_data["linkedon"] = date();
                        $twitter->insert($provider_data);
                        break;
                    case "openid":
                    	$provider_data["account_id"] = $account_id;
                        $provider_data["openid"] = $user_data["provider_id"];
						$provider_data["linkedon"] = date();
                        $openid->insert($provider_data);
                        break;
                    default:
                        $this->_redirect('account/sign_up');
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

