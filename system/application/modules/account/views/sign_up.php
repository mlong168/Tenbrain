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
		<?php echo form_open(uri_string(), array('class' => 'signup_form')); ?>
			
			<p class="input_wrapper">
				<?php echo form_label(lang('sign_up_username'), 'sign_up_username'); ?>
				<?php echo form_input(array(
						'name'		=> 'sign_up_username',
						'id'		=> 'sign_up_username',
						'class'		=> 'control input',
						'value'		=> set_value('sign_up_username'),
						'maxlength'	=> '24'
					)); ?>
				<?php echo form_error('sign_up_username'); ?>
				<?php if (isset($sign_up_username_error)) : ?>
				<span class="field_error"><?php echo $sign_up_username_error; ?></span>
				<?php endif; ?>
			</p>
			<p class="input_wrapper">
				<?php echo form_label(lang('sign_up_password'), 'sign_up_password'); ?>
				<?php echo form_password(array(
						'name' => 'sign_up_password',
						'id' => 'sign_up_password',
						'class'		=> 'control input',
						'value' => set_value('sign_up_password')
					)); ?>
				<?php echo form_error('sign_up_password'); ?>
			</p>
			<p class="input_wrapper">
				<?php echo form_label(lang('sign_up_email'), 'sign_up_email'); ?>
				<?php echo form_input(array(
						'name' => 'sign_up_email',
						'id' => 'sign_up_email',
						'class'		=> 'control input',
						'value' => set_value('sign_up_email'),
						'maxlength' => '160'
					)); ?>
				<?php echo form_error('sign_up_email'); ?>
				<?php if (isset($sign_up_email_error)) : ?>
				<span class="field_error"><?php echo $sign_up_email_error; ?></span>
				<?php endif; ?>
			</p>
			
			<?php if (isset($recaptcha)) : ?>
				<p class="input_wrapper">
					<?php echo $recaptcha; ?>
				</p>
				
				<?php if (isset($sign_up_recaptcha_error)) : ?>
				<p class="input_wrapper">
					<span class="field_error"><?php echo $sign_up_recaptcha_error; ?></span>
				</p>
				<?php endif; ?>			
			<?php endif; ?>
			
			<p class="input_wrapper">
				<?php echo form_button(array(
						'type' => 'submit',
						'class' => 'login_submit',
						'content' => lang('sign_up_create_my_account')
					)); ?>
			</p>
			
		<?php echo form_close(); ?>
		<p class="input_wrapper">
			<?php echo lang('sign_up_already_have_account'); ?>
			<?php echo anchor('account/sign_in', lang('sign_up_sign_in_now'), array('class' => 'register')); ?>
		</p>
	</div>
	<div class="clearfix"></div>
	<div class="prefix_1 grid_5">
		<h3><?php echo sprintf(lang('sign_up_third_party_heading')); ?></h3>
		<ul class="third_parties">
			<?php foreach($this->config->item('third_party_auth_providers') as $provider) : ?>
			<li class="third_party <?php echo $provider; ?>"><?php echo anchor('account/connect_'.$provider, lang('connect_'.$provider), 
				array('title'=>sprintf(lang('sign_up_with'), lang('connect_'.$provider)))); ?></li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>
<?php $this->load->view('help') ?>
<?php $this->load->view('footer') ?>