<?php
	$this->load->view('header', array(
		'title'		=> 'Welcome',
		'styles'	=> array('main'),
		'scripts'	=> array('jquery-1.4.4.min', 'jquery-ui-1.8.9.custom.min', 'main'),
	));
?>
<div id="welcome_message">Welcome to TenBrain!</div>
<?php $this->load->view('footer') ?>