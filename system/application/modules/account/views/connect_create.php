<?php
	$this->load->view('header', array(
		'title'		=> 'Welcome',
		'styles'	=> array('main', 'account'),
		'scripts'	=> array('jquery-1.4.4.min', 'jquery-ui-1.8.9.custom.min', 'main'),
	));
?>
<div class="main big_content">
	<div class="reg_form">
        <h1><?php echo lang('website_title'); ?></h1>
        <h2><?php echo lang('connect_create_account'); ?></h2>
        <?php echo form_open(uri_string()); ?>
			<h3><?php echo lang('connect_create_heading'); ?></h3>
			<?php if (isset($connect_create_error)) : ?>
			<div class="grid_6 alpha">
				<div class="form_error"><?php echo $connect_create_error; ?></div>
			</div>
			<div class="clear"></div>
			<?php endif; ?>
			<div class="grid_2 alpha">
				<?php echo form_label(lang('connect_create_username'), 'connect_create_username'); ?>
			</div>
			<div class="grid_4 omega">
				<?php echo form_input(array(
						'name' => 'connect_create_username',
						'id' => 'connect_create_username',
						'value' => set_value('connect_create_username') ? set_value('connect_create_username') : (isset($connect_create[0]['username']) ? $connect_create[0]['username'] : ''),
						'maxlength' => '16'
					)); ?>
				<?php echo form_error('connect_create_username'); ?>
				<?php if (isset($connect_create_username_error)) : ?>
				<span class="field_error"><?php echo $connect_create_username_error; ?></span>
				<?php endif; ?>
			</div>
			<div class="clear"></div>
			<div class="grid_2 alpha">
				<?php echo form_label(lang('connect_create_email'), 'connect_create_email'); ?>
			</div>
			<div class="grid_4 omega">
				<?php echo form_input(array(
						'name' => 'connect_create_email',
						'id' => 'connect_create_email',
						'value' => set_value('connect_create_email') ? set_value('connect_create_email') : (isset($connect_create[0]['email']) ? $connect_create[0]['email'] : ''),
						'maxlength' => '160'
					)); ?>
				<?php echo form_error('connect_create_email'); ?>
				<?php if (isset($connect_create_email_error)) : ?>
				<span class="field_error"><?php echo $connect_create_email_error; ?></span>
				<?php endif; ?>
			</div>
			<div class="clear"></div>
			<div class="prefix_2 grid_4 alpha">
				<?php echo form_button(array(
						'type' => 'submit',
						'class' => 'button',
						'content' => lang('connect_create_button')
					)); ?>
			</div>
			<div class="clear"></div>
        <?php echo form_close(); ?>
    </div>
</div>
<?php $this->load->view('help') ?>
<?php $this->load->view('footer') ?>