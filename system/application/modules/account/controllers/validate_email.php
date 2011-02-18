<?php
/*
 * Validate_email Controller
 */
class Validate_email extends Controller {

	function __construct()
	{
		parent::Controller();
		
		// Load the necessary stuff...
		$this->load->config('account/account');
		$this->load->helper(array('date', 'language', 'account/ssl', 'url'));
        $this->load->library(array('account/authentication', 'form_validation'));
		$this->load->model(array('account/account_model'));
		$this->load->language(array('general', 'account/email_validate'));
		
		// enable GET
		$query_string = substr(strrchr($_SERVER['REQUEST_URI'], "?"), 1);
		parse_str($query_string, $_GET);
		$_SERVER['QUERY_STRING'] = $query_string;
	}
	
	/**
	 * Validate email
	 */
	function index() 
	{
		// Enable SSL?
		maintain_ssl($this->config->item("ssl_enabled"));
		
		if ($account = $this->account_model->get_by_id($this->input->get('id')))
		{
			// Check if token is valid
			$token = $this->input->get('token');
			if ($token == sha1($account->id . $this->config->item('password_reset_secret')))
			{
				// Remove reset sent on datetime
				$this->account_model->set_verifiedon($account->id);
				
				// Run sign in routine
				$this->authentication->sign_in($account->id);
			}
			else if(empty($token))
			{
				if($this->authentication->is_signed_in())
				{
					$this->load->library('email');
					$email_validate_url = site_url('account/validate_email?id='.$account->id.'&token='.sha1($account->id.$this->config->item('password_reset_secret')));
					
					// Send validation email
					$this->email->from($this->config->item('password_reset_email'), lang('validate_email_sender'));
					$this->email->to($account->email);
					$this->email->subject(lang('welcome_email_subject'));
					$this->email->message($this->load->view('email_validate', array('username' => $account->username, 'email_validate_url' => anchor($email_validate_url, $email_validate_url)), TRUE));
					@$this->email->send();
					
					$this->load->view('email_verification_sent', isset($data) ? $data : NULL);
				}
			}
		}
		
		// Load reset password unsuccessful view
		$this->load->view('email_verification_unsuccessful', isset($data) ? $data : NULL);
	}
	
}


/* End of file validate_email.php */
/* Location: ./system/application/modules/account/controllers/validate_email.php */