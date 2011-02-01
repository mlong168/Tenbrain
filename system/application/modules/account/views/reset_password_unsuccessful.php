<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo lang('reset_password_page_name'); ?></title>
<base href="<?php echo base_url(); ?>" />
<link rel="shortcut icon" href="<?php echo base_url(); ?>favicon.ico" />
<link type="text/css" rel="stylesheet" href="resource/app/css/960gs.css" />
<link type="text/css" rel="stylesheet" href="resource/app/css/style.css" />
</head>
<body>
<?php echo $this->load->view('header'); ?>
<div class="container_12">
	<div class="grid_12">
		<?php echo lang('reset_password_unsuccessful'); ?>
		<p><?php echo anchor('account/forgot_password', lang('reset_password_resend'), array('class'=>'button')); ?></p>
	</div>
	<div class="clear"></div>
</div>
<?php echo $this->load->view('footer'); ?>
</body>
</html>