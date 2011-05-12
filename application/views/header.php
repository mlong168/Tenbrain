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
<div id="container">
	<div id="header">
		<a href="<?php echo base_url() ?>" id="logo">Home</a>
		<!--<span class="title">| Save your Brain, While saving Money! |</span>-->
		
		<div id="account_area">
		<?php if($this->authentication->is_signed_in()): ?>
			<div class="signed_in_controls">
				<span class="welcome_message">Welcome, <?php echo $this->account_model->get_by_id($this->session->userdata('account_id'))->username ?>!</span><br />
				<a class="blue underlined_dash" href="/account/sign_out">Sign out</a>
				<a class="blue underlined_dash" href="/control_panel">Profile</a>
			</div>
		<?php else: ?>
			<a class="blue underlined_dash" href="/account/sign_up">Register</a>
			<span class="login_submit" id="signin_opener">Sign in</span>
			<?php echo form_open('/account/sign_in', array('id'	=> 'signin_controls')) ?>
				<p class="form_contents small">
					<label for="username" class="login_label">Username/Email:</label>
				</p>
				<p class="form_contents">
					<input id="username" name="sign_in_username_email" class="signin_control" type="text" size="25" />
				</p>
				<p class="form_contents small">
					<label for="password" class="login_label">Password:</label>
				</p>
				<p class="form_contents">
					<input id="password" name="sign_in_password" class="signin_control" type="password" size="25" />
				</p>
				<p class="form_contents">
					<span class="centered">
						<input type="checkbox" id="sign_in_remember" name="sign_in_remember" checked="checked" class="centered" />
						<label for="sign_in_remember" class="login_label centered">Remember me</label>
					</span>
					<input type="submit" id="sign_in" class="login_submit centered" value="Sign in" />
				</p>
				<p class="form_contents centered">
					<span class="third_party_header">Sign in with your account from:</span>
				</p>
				<div class="form_contents">
					<ul class="signin_social">
						<?php $this->config->load('account/config') ?>
						<?php foreach($this->config->item('third_party_auth_providers') as $provider) : ?>
						<li class="control third_party small <?php echo $provider; ?>">
							<?php echo anchor('account/connect_'.$provider,
								lang('connect_'.$provider), 
								array(
									'title' => sprintf(lang('sign_up_with'), lang('connect_'.$provider)),
									'class'	=> 'social'
								)
							); ?>
						</li>
						<?php endforeach; ?>
					</ul>				
				</div>
			<?php echo form_close() ?>
		<?php endif; ?>
		</div>
		<div class="clearfix"></div>
	</div>