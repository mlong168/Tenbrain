<?php
	$this->load->view('header', array(
		'title'		=> 'Welcome',
		'styles'	=> array('main', 'account'),
		'scripts'	=> array('jquery-1.4.4.min', 'jquery-ui-1.8.9.custom.min', 'main'),
	));
?>
<div class="main small_content">
	<div class="reg_form">
		<h2><?php echo sprintf(lang('connect_with_x'), lang('connect_openid')); ?></h2>
		<?php echo form_open(uri_string()); ?>
			<h3><?php echo sprintf(lang('connect_enter_your'), lang('connect_openid_url')); ?> 
			<small><?php echo anchor($this->config->item('openid_what_is_url'), 
				lang('connect_start_what_is_openid'), 
				array(
					'target'	=> '_blank',
					'class'		=> 'register'
				)); ?>
			</small>
			</h3>
				
			<?php if (isset($connect_openid_error)) : ?>	
			<p class="account_error"><?php echo $connect_openid_error; ?></p>
			<?php endif; ?>
			
			<?php if ($this->session->flashdata('connect_openid_error')) : ?>	
			<p class="account_error"><?php echo $this->session->flashdata('connect_openid_error'); ?></p>
			<?php endif; ?>	
			
			<p class="input_wrapper center">
				<?php echo form_input(array(
						'name' => 'connect_openid_url',
						'id' => 'connect_openid_url',
						'class' => 'openid_url',
						'value' => set_value('connect_openid_url')
					)); ?>
				<p class="input_wrapper center account_error"><?php echo form_error('connect_openid_url'); ?></p>
			</p>	
			<p class="input_wrapper center">
				<?php echo form_button(array(
						'type' => 'submit',
						'class' => 'login_submit',
						'content' => lang('connect_proceed')
					)); ?>
				<br /><br />
			<?php echo anchor('account/sign_up', 'Back to signup screen', array('class' => 'register')); ?>
			</p>
		<?php echo form_close(); ?>
	</div>
</div>
<?php $this->load->view('help') ?>
<?php $this->load->view('footer') ?>