<?php
	$this->load->view('header', array(
		'title'		=> 'Welcome',
		'styles'	=> array('main', 'account'),
		'scripts'	=> array('jquery-1.4.4.min', 'jquery-ui-1.8.9.custom.min', 'main'),
	));
?>
<div class="main big_content">
	<div class="reg_form">
		<h2><?php echo lang('sign_up_page_name') ?></h2>
		<h3><?php echo lang('sign_up_heading'); ?></h3>
		<?php echo form_open(uri_string(), array('class' => 'account_form signup')); ?>			
			<p class="input_wrapper">
				<?php echo form_label(lang('sign_up_username'), 'sign_up_username'); ?>:
				<?php echo form_input(array(
						'name'		=> 'sign_up_username',
						'id'		=> 'sign_up_username',
						'class'		=> 'control input',
						'value'		=> set_value('sign_up_username'),
						'maxlength'	=> '24'
					)); ?>
				<p class="input_wrapper center account_error"><?php echo form_error('sign_up_username'); ?></p>
				<?php if (isset($sign_up_username_error)) : ?>
				<p class="input_wrapper center account_error"><?php echo $sign_up_username_error; ?></p>
				<?php endif; ?>
			</p>
			<p class="input_wrapper">
				<?php echo form_label(lang('sign_up_password'), 'sign_up_password'); ?>:
				<?php echo form_password(array(
						'name'	=> 'sign_up_password',
						'id'	=> 'sign_up_password',
						'class'	=> 'control input',
						'value'	=> set_value('sign_up_password')
					)); ?>
				<p class="input_wrapper center account_error"><?php echo form_error('sign_up_password'); ?></p>
			</p>
			<p class="input_wrapper">
				<?php echo form_label(lang('sign_up_email'), 'sign_up_email'); ?>:
				<?php echo form_input(array(
						'name'		=> 'sign_up_email',
						'id'		=> 'sign_up_email',
						'class'		=> 'control input',
						'value'		=> set_value('sign_up_email'),
						'maxlength'	=> '160'
					)); ?>
				<p class="input_wrapper center account_error"><?php echo form_error('sign_up_email'); ?></p>
				<?php if (isset($sign_up_email_error)) : ?>
				<p class="input_wrapper center account_error"><?php echo $sign_up_email_error; ?></p>
				<?php endif; ?>
			</p>
			
			<?php if (isset($recaptcha)) : ?>
				<div class="input_wrapper">
					<?php echo $recaptcha; ?>
				</div>
				
				<?php if (isset($sign_up_recaptcha_error)) : ?>
				<p class="input_wrapper center account_error"><?php echo $sign_up_recaptcha_error; ?></p>
				<?php endif; ?>			
			<?php endif; ?>
			
			<p class="input_wrapper">
				<?php echo form_button(array(
						'type'		=> 'submit',
						'class'		=> 'login_submit underlined_dash',
						'content'	=> lang('sign_up_create_my_account')
					)); ?>
			</p>
			
		<?php echo form_close(); ?>
		<p class="input_wrapper center">
			<?php echo lang('sign_up_already_have_account'); ?>
			<?php echo anchor('account/sign_in', lang('sign_up_sign_in_now'), array('class' => 'blue underlined_dash')); ?>
		</p>
	</div>
	<h3><?php echo sprintf(lang('sign_up_third_party_heading')); ?>:</h3>
	<ul class="third_parties">
		<?php foreach($this->config->item('third_party_auth_providers') as $provider) : ?>
		<li class="control third_party big <?php echo $provider; ?>">
			<?php echo anchor('account/connect_'.$provider,
				lang('connect_'.$provider), 
				array(
					'title'	=> sprintf(lang('sign_up_with'), lang('connect_'.$provider)),
					'class'	=> 'social'
				)
			); ?>
		</li>
		<?php endforeach; ?>
	</ul>
	<div class="clearfix"></div>
</div>
<?php $this->load->view('help') ?>
<?php $this->load->view('footer') ?>