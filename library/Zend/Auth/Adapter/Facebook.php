<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Auth
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id: Result.php 20096 2010-01-06 02:05:09Z bkarwin $
 */


/**
 * @category   Zend
 * @package    Zend_Auth
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Auth_Adapter_Facebook implements Zend_Auth_Adapter_Interface
{
    private $_appId;
    private $_secret;
    private $_redirect_uri;
    private $_permissions;
    private $_token;
    private $_url           = 'https://graph.facebook.com/oauth/access_token';

    /**
    * List of query parameters that get automatically dropped when rebuilding
    * the current URL.
    */
    protected static $DROP_QUERY_PARAMS = array(
        'session',
        'signed_request',
    );

    /**
     * @param mixed $config - Zend_Config or array
     */
    public function __construct($config)
    {
        if($config instanceof Zend_Config) {
            $config = $config->toArray();
        }
        
        if (isset($config['appId'])) {
            $this->_appId = $config['appId'];
            unset($config['appId']);
        } else {
            throw new Zend_Auth_Exception('Required param "appId" is missing param in config');
        }
        
   		if (isset($config['redirect_uri'])) {
            $this->_redirect_uri = $config['redirect_uri'];
            unset($config['redirect_uri']);
        } else {
            throw new Zend_Auth_Exception('Required param "redirect_uri" is missing param in config');
        }

        if (isset($config['secret'])) {
            $this->_secret = $config['secret'];
            unset($config['secret']);
        } else {
            throw new Zend_Auth_Exception('Required param "secret" is missing param in config');
        }

        if (isset($config['permissions'])) {
            $this->_permissions = $config['permissions'];
            unset($config['permissions']);
        } else {
            $this->_permissions = '';
        }
    }

    /**
     * Set token You have received from Facebook
     * @param string $token
     * @return Zend_Auth_Adapter_Facebook
     */
    public function setToken($token)
    {
        $this->_token = $token;
        return $this;
    }

    /**
     * Exchange token to access_token
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        if (empty($this->_token)) {
            $code = Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID;
            return new Zend_Auth_Result($code, false, 'Set Token for authorization');
        }

        $args = array(
            'client_id'     => $this->_appId,
            'client_secret' => $this->_secret,
            'redirect_uri'  => $this->_redirect_uri,
            'code'          => $this->_token,
        );
        
        $response = $this->_apiCall($this->_url, $args);
        if (isset($response['error'])) {
            $code = Zend_Auth_Result::FAILURE;
            $msgs[] = $response['error']['message'];
            $result = new Zend_Auth_Result($code, false, $msgs);
        } else {
            $code = Zend_Auth_Result::SUCCESS;
            $identity = $response['access_token'];
            $result = new Zend_Auth_Result($code, $identity);
        }

        return $result;
    }

    /**
     * Make post to url
     * @param string $url
     * @param array $params
     * @return array
     */
    protected function _apiCall($url, $params)
    {
        $r = new Zend_Http_Client($url);
        $r->setParameterGet($params);
        $response = $r->request(Zend_Http_Client::GET);
        $res = json_decode($response->getBody(), true);
        if(is_array($res)) {
            return $res;
        } else {
            $access_token = str_replace('access_token=', '', $response->getBody());
            return array('access_token' => $access_token);
        }
    }

    /**
     * Redirect to login page
     */
    public function redirect()
    {
        $params = array(
            'scope'         => $this->_permissions,
            'client_id'     => $this->_appId,
            'redirect_uri'  => $this->_redirect_uri,
        );
        $url = 'https://graph.facebook.com/oauth/authorize?';
        $url .= http_build_query($params, null, '&');
        header('Location: '.$url);
        exit();
    }
}
