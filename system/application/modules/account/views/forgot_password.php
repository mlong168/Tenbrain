<?php
	$this->load->view('header', array(
		'title'		=> 'Welcome',
		'styles'	=> array('main', 'account'),
		'scripts'	=> array('jquery-1.4.4.min', 'jquery-ui-1.8.9.custom.min', 'main'),
	));
?>
<div class="main big_content">
	<div class="reg_form">
		<h2><?php echo lang('forgot_password_page_name'); ?></h2>
		<?php echo form_open(uri_string()); ?>
		<?php echo form_fieldset(); ?>
		<p><?php echo lang('forgot_password_instructions'); ?></p>
		<div class="grid_2 alpha">
			<?php echo form_label(lang('forgot_password_username_email'), 'forgot_password_username_email'); ?>
		</div>
		<div class="grid_6 omega">
			<?php echo form_input(array(
					'name' => 'forgot_password_username_email',
					'id' => 'forgot_password_username_email',
					'value' => set_value('forgot_password_username_email') ? set_value('forgot_password_username_email') : (isset($account) ? $account->username : ''),
					'maxlength' => '80'
				)); ?>
			<?php echo form_error('forgot_password_username_email'); ?>
			<?php if (isset($forgot_password_username_email_error)) : ?>
			<span class="field_error"><?php echo $forgot_password_username_email_error; ?></span>
			<?php endif; ?>
		</div>
		<div class="clear"></div>
		<?php if (isset($recaptcha)) : ?>
		<div class="prefix_2 grid_8 alpha">
			<?php echo $recaptcha; ?>
		</div>
		<?php if (isset($forgot_password_recaptcha_error)) : ?>
		<div class="prefix_2 grid_6 alpha">
			<span class="field_error"><?php echo $forgot_password_recaptcha_error; ?></span>
		</div>
		<?php endif; ?>
		<div class="clear"></div>
		<?php endif; ?>
		<div class="prefix_2 grid_6 alpha">
			<?php echo form_button(array(
					'type' => 'submit',
					'class' => 'button',
					'content' => lang('forgot_password_send_instructions')
				)); ?>
		</div>
		<?php echo form_fieldset_close(); ?>
		<?php echo form_close(); ?>
	</div>
</div>
<?php $this->load->view('help') ?>
<?php $this->load->view('footer') ?>