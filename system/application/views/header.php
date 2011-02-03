<?php echo doctype('xhtml1-trans') ?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $title ?> - TenBrain - Save your Brain, While saving Money!</title>
	
	<?php if(count($styles)): ?>
		<?php foreach($styles as $name): ?>
			<link rel="stylesheet" type="text/css" href="<?php echo base_url() . 'css/' . $name ?>.css" />
		<?php endforeach; ?>
	<?php endif; ?>
	
	<?php if(count($scripts)): ?>
		<?php foreach($scripts as $name): ?>
			<script type="text/javascript" src="<?php echo base_url() . 'js/' . $name ?>.js"></script>
		<?php endforeach; ?>
	<?php endif; ?>
</head>
<body>

<div id="header">
	<a href="<?php echo base_url() ?>" id="logo">Home</a>
	<span class="title">| Save your Brain, While saving Money! |</span>
	
	<div class="account_area">
	<?php if($this->authentication->is_signed_in()): ?>
		<div class="signed_in_controls">
			<span class="welcome_message">Welcome, <?php echo $this->user->username ?>!</span><br />
			<a class="register" href="/account/sign_out">Sign out</a>
			<a class="register" href="/account/profile">Profile</a>
		</div>
	<?php else: ?>
		<?php echo form_open('/account/sign_in', array('id'	=> 'login_form')) ?>
			<p class="form_contents">
				<label for="username" class="login_label">Username/Email</label>
				<input id="username" name="sign_in_username_email" class="control input" name="username" type="text" size="25" />
			</p>
			<p class="form_contents">
				<label for="password" class="login_label">Password</label>
				<input id="password" name="sign_in_password" class="control input" name="password" type="password" size="25" />
			</p>
			<p class="form_contents">
				<a class="register" href="/account/sign_up">Register</a>
				<input type="submit" class="login_submit" value="Log in" />
			</p>
		<?php echo form_close() ?>
	<?php endif; ?>
	</div>
</div>