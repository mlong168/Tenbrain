<?php
class Paypal_Main

{
	private $DEFAULT_DEV_CENTRAL = "developer";
	private $DEFAULT_ENV = "sandbox";
	private $DEFAULT_EMAIL_ADDRESS = "sdk.seller@gmail.com";
	private $DEFAULT_IDENTITY_TOKEN = "6vwLEY_ogPGnoQac2a0x4PRsSGrmzJPMkyGbJtpiCSwrkYsNSYxWfPY2ZLO";
	private $DEFAULT_EWP_CERT_PATH = "../library/Paypal/cert/my-pubcert.pem";
	private $DEFAULT_EWP_PRIVATE_KEY_PATH = "../library/Paypal/cert/my-prvkey.pem";
	private $DEFAULT_EWP_PRIVATE_KEY_PWD = "password";
	private $DEFAULT_CERT_ID = "B62GVU8RWNBFC";
	private $PAYPAL_CERT_PATH = "../library/Paypal/cert/paypal_cert_pem.txt";
	private $BUTTON_IMAGE = "/css/images/paypal_buttons/x-click-but23.gif";
	private $PAYPAL_IPN_LOG = "paypal-ipn.log";
    
    public function BuyButton(){
    	return $this->BUTTON_IMAGE;
    }
    
    public function CryptedBuyButton($url){
		
    	$baseURL = $this->getBaseUrl($url) . '/';
		$buttonParams = array(	"cmd"			=> "_xclick",
								"business" 		=> $this->DEFAULT_EMAIL_ADDRESS,
								"cert_id"		=> $this->DEFAULT_CERT_ID,
								"charset"		=> "UTF-8",
								"item_name"		=> "Tenbrain Pro",
								"item_number"	=> "1",
								"amount"		=> "1.99",
								"currency_code"	=> "USD",
								"return"		=> $baseURL."success",
								"cancel_return"	=> $baseURL."cancel",
								"notify_url"	=> $baseURL."notify",
								"custom"		=> "Tenbrain Pro. Account update.");
		
		$envURL = "https://www.".$this->DEFAULT_ENV.".paypal.com";
		
		$buttonReturn = Paypal_EWPServices::encryptButton(	$buttonParams,
													realpath($this->DEFAULT_EWP_CERT_PATH),
													realpath($this->DEFAULT_EWP_PRIVATE_KEY_PATH),
													$this->DEFAULT_EWP_PRIVATE_KEY_PWD,
													realpath($this->PAYPAL_CERT_PATH),
													$envURL,
													$this->BUTTON_IMAGE);
													
		return $buttonReturn;		
    }
    
    public function getBaseUrl($param = '') {
    	return Paypal_Utils::getURL($param);
    }
}
?>