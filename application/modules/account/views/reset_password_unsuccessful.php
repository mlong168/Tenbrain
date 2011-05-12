<?php
	$this->load->view('header', array(
		'title'		=> 'Welcome',
		'styles'	=> array('main', 'account'),
		'scripts'	=> array('jquery-1.4.4.min', 'jquery-ui-1.8.9.custom.min', 'main'),
	));
?>
<div class="main small_content">
	<div class="input_wrapper center">
		<?php echo lang('reset_password_unsuccessful'); ?>
		<p><?php echo anchor('account/forgot_password', lang('reset_password_resend'), array('class'=>'button')); ?></p>
	</div>
</div>
<?php echo $this->load->view('help'); ?>
<?php echo $this->load->view('footer'); ?>