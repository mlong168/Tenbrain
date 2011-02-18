<?php
	$this->load->view('header', array(
		'title'		=> 'Welcome',
		'styles'	=> array('main', 'account'),
		'scripts'	=> array('jquery-1.4.4.min', 'jquery-ui-1.8.9.custom.min', 'main'),
	));
?>
<div class="main small_content">
	<div class="input_wrapper center">
		<?php echo lang('email_verification_unsuccessful'); ?>
		<p><?php echo anchor('account/validate_email', lang('email_verification_resend'), array('class'=>'button')); ?></p>
	</div>
</div>
<?php echo $this->load->view('help'); ?>
<?php echo $this->load->view('footer'); ?>