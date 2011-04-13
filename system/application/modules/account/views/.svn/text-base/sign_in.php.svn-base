<?php
	$this->load->view('header', array(
		'title'		=> 'Welcome',
		'styles'	=> array('main', 'account'),
		'scripts'	=> array('jquery-1.4.4.min', 'jquery-ui-1.8.9.custom.min', 'main'),
	));
?>
<div class="main big_content">
	<div class="reg_form">
		<h2><?php echo lang('sign_in_page_name'); ?></h2>
		<?php echo form_open(uri_string(), array('class' => 'account_form signin')); ?>
			<h3><?php echo lang('sign_in_heading'); ?></h3>
			<?php if (isset($sign_in_error)) : ?>
			<p class="input_wrapper center account_error"><?php echo $sign_in_error; ?></p>
			<?php endif; ?>
			<p class="input_wrapper">
				<?php echo form_label(lang('sign_in_username_email'), 'sign_in_username_email'); ?>
				<?php echo form_input(array(
						'name'		=> 'sign_in_username_email',
						'id'		=> 'sign_in_username_email',
						'class'		=> 'control input',
						'value'		=> set_value('sign_in_username_email'),
						'maxlength'	=> '24'
					)); ?>
					
				<p class="input_wrapper center account_error"><?php echo form_error('sign_in_username_email'); ?></p>
				<?php if (isset($sign_in_username_email_error)) : ?>
				<p class="input_wrapper center account_error"><?php echo $sign_in_username_email_error; ?></p>
				<?php endif; ?>
			</p>
			<p class="input_wrapper">
				<?php echo form_label(lang('sign_in_password'), 'sign_in_password'); ?>
				<?php echo form_password(array(
						'name'	=> 'sign_in_password',
						'id'	=> 'sign_in_password',
						'class'	=> 'control input',
						'value'	=> set_value('sign_in_password')
					)); ?>
			</p>
			<p class="input_wrapper center account_error"><?php echo form_error('sign_in_password'); ?></p>
			<?php if (isset($recaptcha)) : ?>
				<div class="input_wrapper">
					<?php echo $recaptcha; ?>
				</div>
				<?php if (isset($sign_in_recaptcha_error)) : ?>
					<p class="input_wrapper center account_error"><?php echo $sign_in_recaptcha_error; ?></p>
				<?php endif; ?>
			<?php endif; ?>
			<p class="input_wrapper">
				<?php echo form_checkbox(array(
						'name'		=> 'sign_in_remember',
						'id'		=> 'sign_in_remember',
						'value'		=> 'checked',
						'checked'	=> $this->input->post('sign_in_remember'),
						'class'		=> 'checkbox'
					)); ?>
				<?php echo form_label(lang('sign_in_remember_me'), 'sign_in_remember'); ?>
				<?php echo form_button(array(
						'type'		=> 'submit',
						'class'		=> 'login_submit underlined_dash',
						'content'	=> lang('sign_in_sign_in')
					)); ?>
			</p>
			<p class="input_wrapper center">
				<?php echo anchor('account/forgot_password', lang('sign_in_forgot_your_password'), array('class' => 'blue underlined_dash')); ?>
			</p>
			<p class="input_wrapper center">
				<?php echo sprintf(lang('sign_in_dont_have_account'), anchor('account/sign_up', lang('sign_in_sign_up_now'), array('class' => 'blue underlined_dash'))); ?>
			</p>
		<?php echo form_close(); ?>
	</div>
	<h3><?php echo sprintf(lang('sign_in_third_party_heading')); ?>:</h3>
	<ul class="third_parties">
		<?php foreach($this->config->item('third_party_auth_providers') as $provider) : ?>
		<li class="control third_party big <?php echo $provider; ?>">
			<?php echo anchor('account/connect_'.$provider,
				lang('connect_'.$provider), 
				array(
					'title'	=> sprintf(lang('sign_in_with'), lang('connect_'.$provider)),
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