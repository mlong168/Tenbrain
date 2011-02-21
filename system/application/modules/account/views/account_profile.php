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
	<h2><?php echo lang('profile_page_name'); ?></h2><br />
	<?php echo form_open_multipart(uri_string()); ?>
	
	<?php if (isset($profile_info)) : ?>
	<p class="center"><?php echo $profile_info; ?></p>
	<?php endif; ?>
	
	<p class="center"><?php echo lang('profile_instructions'); ?></p>
	<p class="input_wrapper center">
		<?php echo form_label(lang('profile_username'), 'profile_username'); ?>
		<?php echo form_input(array(
				'name'		=> 'profile_username',
				'id'		=> 'profile_username',
				'value'		=> set_value('profile_username') ? set_value('profile_username') : (isset($account->username) ? $account->username : ''),
				'maxlength'	=> '24'
			)); ?>
		<?php echo form_error('profile_username'); ?>
		<?php if (isset($profile_username_error)) : ?>
		<span class="field_error"><?php echo $profile_username_error; ?></span>
		<?php endif; ?>
	</p>
	<div class="input_wrapper center">
		<?php echo form_label(lang('profile_picture'), 'profile_picture'); ?>
		<?php if (isset($account_details->picture)) : ?>
			<img id="profile_picture" src="<?php echo $account_details->picture; ?>?t=<?php echo md5(time()); ?>" alt="" /> <?php echo anchor('account/account_profile/index/delete', lang('profile_delete_picture')); ?>
		<?php else : ?>
			<img id="profile_picture" src="resource/app/img/default-picture.gif" alt="" />
		<?php endif; ?>
		
		<?php echo form_upload(array(
			'name'	=> 'account_picture_upload',
			'id'	=> 'account_picture_upload'
		)); ?>
		<p><small><?php echo lang('profile_picture_guidelines'); ?></small></p>
		<?php if (isset($profile_picture_error)) : ?>
		<span class="field_error"><?php echo $profile_picture_error; ?></span>
		<?php endif; ?>
	</div>
	<p class="input_wrapper center">
		<?php echo form_button(array(
			'name'		=> 'submit',
			'type'		=> 'submit',
			'class'		=> 'login_submit',
			'content'	=> lang('profile_save')
		)); ?>
	</p>
	<?php echo form_close(); ?>
</div>

</body></html>