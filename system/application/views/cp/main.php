<?php
	$this->load->view('cp/header', array(
		'title'		=> 'TenBrain Control Panel',
		'styles'	=> array('main', 'account', 'ext_resources/css/ext-all'),
		'scripts'	=> array(
			'jquery-1.4.4.min',
			'jquery-ui-1.8.9.custom.min',
			'extjs/adapter/jquery/ext-jquery-adapter',
			'extjs/ext-all', // switch to ext-all on production!
			'cp/cloud', 'cp/profile', 'cp/cp')
	));
?>
<div id="header">
	<a href="/" id="logo">Home</a>
	<div id="account_area">
		<div class="signed_in_controls">
			<span class="welcome_message">Welcome, slavko!</span>
			<a class="blue underlined_dash" href="/account/sign_out">Sign out</a>
		</div>
	</div>
</div>
<div style="display:none;">
	
	<div id="welcome-div">
		<h2>Welcome!</h2>
		<p>Welcome text to be displayed here</p>
	</div>
	
	<div id="running_instances-details">
		<h2>Your running instances</h2>
		<p>This page shows the instances you deployed.</p>
	</div>
	
	<div id="pending_instances-details">
		<h2>Instances that pending to be launched</h2>
		<p>This page shows the instances you deployed.</p>
	</div>
	
	<div id="stopped_instances-details">
		<h2>Instances that have been stopped</h2>
		<p>This page shows the instances you deployed.</p>
	</div>
	
	<div id="terminated_instances-details">
		<h2>Instances that have previously been terminated</h2>
		<p>This page shows the instances you deployed.</p>
	</div>
	
	<div id="available_images-details">
		<h2>Images available for deployment</h2>
		<p>This page shows the images available for deployment.</p>
	</div>
	
	<div id="account_profile-details">
		<h2>Your profile details</h2>
		<p>This page shows the instances you deployed and that are currently active.</p>
	</div>
	
	<div id="account_settings-details">
		<h2>Your profile settings</h2>
		<p>This page shows the instances you deployed and that are currently active.</p>
	</div>
	
	<div id="account_password-details">
		<h2>Password information</h2>
		<p>This page shows the instances you deployed and that are currently active.</p>
	</div>
	
	<div id="account_linked-details">
		<h2>Linked Accounts</h2>
		<p>This page shows the instances you deployed.</p>
	</div>
	
</div>
<div id="footnote" style="text-align:center"><p>All rights reserved &copy; <?php echo date('Y') ?>, TenBrain</p></div>
<?php $this->load->view('cp/footer') ?>