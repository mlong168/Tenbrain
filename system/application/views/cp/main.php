<?php
	$cp_scripts = array('cp/instances', 'cp/images', 'cp/snapshots', 'cp/profile');
	if($account_type !== 'premium') $cp_scripts []= 'cp/transferer';
	if($account_type === 'premium') $cp_scripts []= 'cp/load_balancers';
	$cp_scripts []= 'cp/cp';
	$this->load->view('cp/header', array(
		'title'		=> 'TenBrain Control Panel',
		'styles'	=> array('main', 'account', 'ext_resources/css/ext-all', 'icons'),
		'scripts'	=> array_merge(array('extjs/adapter/ext/ext-base', 'extjs/ext-all'), $cp_scripts),
		'active_menu_item'	=> $active_menu_item,
		'account_type'		=> $account_type
	));
?>
<div id="header">
	<a href="/" id="logo">Home</a>
	<div id="account_area">
		<div class="signed_in_controls">
			<span class="welcome_message">Welcome, <?php echo $this->account_model->get_by_id($this->session->userdata('account_id'))->username ?>!</span>
			<a class="blue underlined_dash" href="/account/sign_out">Sign out</a><br />
			<span class="welcome_message">Account type: <?php echo $account_type ?></span>
			<?php if($account_type !== 'premium'): ?>
				<a class="blue underlined_dash" href="#" id="upgrader">Upgrage to TenBrain Premium!</a>
			<?php endif; ?>
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
	
	<div id="snapshots-details">
		<h2>Snapshots</h2>
		<p>This page shows the images available for deployment.</p>
	</div>
	
	<div id="load_balancers-details">
		<h2>Load Balancers</h2>
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