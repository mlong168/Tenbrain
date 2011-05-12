<?php
	
	$ext_scripts = array(
		'extjs4/ext-all-debug',
		// 'extjs4/ext3-core-compat',
		// 'extjs4/ext3-compat'
	);

	$cp_scripts = array(
		'cp/instances',
		'cp/images',
		'cp/snapshots',
		'cp/profile',
		'cp/transferer'
	);
	// if($account_type === 'premium') $cp_scripts = array_merge($cp_scripts, array('cp/SuperBoxSelect', 'cp/load_balancers', 'cp/elastic_ips'));
	if($account_type === 'premium') $cp_scripts = array_merge($cp_scripts, array('cp/load_balancers', 'cp/elastic_ips'));
	
	$cp_scripts []= 'cp/cp';
	
	$this->load->view('cp/header', array(
		'title'		=> 'TenBrain Control Panel',
		'styles'	=> array('main', 'account', 'ext4/resources/css/ext-all', 'icons'),
		'scripts'	=> array_merge($ext_scripts, $cp_scripts),
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
			<span class="welcome_message">Account type: <?php echo $account_type ?></span>&nbsp;(<a href="#" class="blue underlined_dash" id="account_type_changer">change</a>)
			
			<a class="blue underlined_dash" href="#" id="cloud_account_manager">Manage your cloud account</a>
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
	
	<div id="elastic_ips-details">
		<h2>Elastic IPs</h2>
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