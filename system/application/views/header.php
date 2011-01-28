<?php echo doctype('html4-trans') ?>
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
	<span class="title">| Save your Brain, While saving Money! |</span>
		<form id="login_form" action="<?php echo base_url() . 'login' ?>" method="post">
			<p>
				<label for="username" class="login_label">Username</label>
				<input id="username" class="control input" name="username" type="text" size="25" />
			</p>
			<p>
				<label for="password" class="login_label">Password</label>
				<input id="password" class="control input" name="password" type="password" size="25" />
			</p>
			<p>
				<a id="register" href="/register">Register</a>
				<input type="submit" class="login_button" id="login_submit" value="Log in" />
			</p>
		</form>
</div>