<?php
	$this->load->view('cp/header', array(
		'title'		=> 'TenBrain Control Panel',
		'styles'	=> array('main', 'account', 'ext_resources/css/ext-all'),
		'scripts'	=> array(
			'jquery-1.4.4.min',
			'jquery-ui-1.8.9.custom.min',
			'extjs/adapter/jquery/ext-jquery-adapter',
			'extjs/ext-all-debug', // switch to ext-all on production!
			'cp')
	));
?>
<?php $this->load->view('cp/footer') ?>