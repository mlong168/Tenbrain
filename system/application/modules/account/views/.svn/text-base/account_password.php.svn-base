<?php echo doctype('xhtml1-trans') ?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>TenBrain - Save your Brain, While saving Money! - Profile</title>
	<link rel="stylesheet" type="text/css" href="<?php echo base_url() ?>css/main.css" />
	<link rel="stylesheet" type="text/css" href="<?php echo base_url() ?>css/account.css" />
</head>
<body>

<div class="profile_content">
	<h2><?php echo lang('password_page_name'); ?></h2><br />
	<?php echo form_open(uri_string()); ?>
	
	<?php if ($this->session->flashdata('password_info')) : ?>
		<p class="input_wrapper center account_success"><?php echo $this->session->flashdata('password_info'); ?></p>
	<?php endif; ?>
	
	<?php echo lang('password_safe_guard_your_account'); ?>
	<div class="input_wrapper center">
		<?php echo form_label(lang('password_new_password'), 'password_new_password'); ?>:
		<?php echo form_password(array(
				'name'	=> 'password_new_password',
				'id'	=> 'password_new_password',
				'value'	=> set_value('password_new_password')
			)); ?>
		<p class="input_wrapper center account_error"><?php echo form_error('password_new_password'); ?></p>
	</div>
	<div class="input_wrapper center">
		<?php echo form_label(lang('password_retype_new_password'), 'password_retype_new_password'); ?>
		<?php echo form_password(array(
				'name'	=> 'password_retype_new_password',
				'id'	=> 'password_retype_new_password',
				'value'	=> set_value('password_retype_new_password')
			)); ?>
		<p class="input_wrapper center account_error"><?php echo form_error('password_retype_new_password'); ?></p>
	</div>
	<p class="input_wrapper center">
		<?php echo form_button(array(
			'name'		=> 'submit',
			'type'		=> 'submit',
			'class'		=> 'login_submit',
			'content'	=> lang('password_change_my_password')
		)); ?>
	</p>
	<?php echo form_close(); ?>
</div>

</body></html>