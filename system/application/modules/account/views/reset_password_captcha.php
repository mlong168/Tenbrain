<?php
	$this->load->view('header', array(
		'title'		=> 'Welcome',
		'styles'	=> array('main', 'account'),
		'scripts'	=> array('jquery-1.4.4.min', 'jquery-ui-1.8.9.custom.min', 'main'),
	));
?>
<div class="main small_content">
	<div class="reg_form">
		<?php echo form_open(uri_string().(empty($_SERVER['QUERY_STRING'])?'':'?'.$_SERVER['QUERY_STRING'])); ?>
			<h2><?php echo lang('reset_password_captcha'); ?></h2>
			<?php if (isset($recaptcha)) : ?>
				<div class="input_wrapper">
					<?php echo $recaptcha; ?>
				</div>
				<?php if (isset($reset_password_recaptcha_error)) : ?>
					<p class="input_wrapper center account_error"><?php echo $reset_password_recaptcha_error; ?></p>
				<?php endif; ?>
			<?php endif; ?>
			<p class="input_wrapper center">
				<?php echo form_button(array(
						'type' => 'submit',
						'class' => 'button',
						'content' => lang('reset_password_captcha_submit')
					)); ?>
			</p>
		<?php echo form_close(); ?>
	</div>
</div>
<?php echo $this->load->view('help'); ?>
<?php echo $this->load->view('footer'); ?>