<?php
	$this->load->view('header', array(
		'title'		=> 'Welcome',
		'styles'	=> array('main', 'account'),
		'scripts'	=> array('jquery-1.4.4.min', 'jquery-ui-1.8.9.custom.min', 'main'),
	));
?>
<div class="main small_content">
	<div class="reg_form">
		<h2><?php echo lang('forgot_password_page_name'); ?></h2>
		<?php echo form_open(uri_string()); ?>
		<p><?php echo lang('forgot_password_instructions'); ?></p>
		<p class="input_wrapper center">
			<?php echo form_label(lang('forgot_password_username_email'), 'forgot_password_username_email'); ?>:
			<?php echo form_input(array(
					'name'		=> 'forgot_password_username_email',
					'id'		=> 'forgot_password_username_email',
						'class'	=> 'control input',
					'value'		=> set_value('forgot_password_username_email') ? set_value('forgot_password_username_email') : (isset($account) ? $account->username : ''),
					'maxlength'	=> '80'
				)); ?>
		</p>
		<p class="input_wrapper center account_error">
			<?php echo form_error('forgot_password_username_email'); ?>
			<?php if (isset($forgot_password_username_email_error)) : ?>
			<span class="field_error"><?php echo $forgot_password_username_email_error; ?></span>
			<?php endif; ?>
		</p>
		<?php if (isset($recaptcha)) : ?>
		<div class="input_wrapper">
			<?php echo $recaptcha; ?>
		</div>
		<?php if (isset($forgot_password_recaptcha_error)) : ?>
		<p class="input_wrapper center account_error"><?php echo $forgot_password_recaptcha_error; ?></p>
		<?php endif; ?>
		<?php endif; ?>
		<p class="input_wrapper center">
			<?php echo form_button(array(
					'type'		=> 'submit',
					'class'		=> 'button',
					'content'	=> lang('forgot_password_send_instructions'),
					'class'		=> 'login_submit underlined_dash'
				)); ?>
		</p>
		<?php echo form_close(); ?>
	</div>
</div>
<?php $this->load->view('help') ?>
<?php $this->load->view('footer') ?>