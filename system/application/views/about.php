<?php
	$this->load->view('header', array(
		'title'		=> 'Welcome',
		'styles'	=> array('main'),
		'scripts'	=> array('jquery-1.4.4.min', 'jquery-ui-1.8.9.custom.min', 'main'),
	));
?>
<div id="main">
	<span class="info_image info">Info</span>
	<p class="selection_message narrow">About us</p>
	<p class="explanation">
		TenBrain is an online deployment and integration platform for Open Source and SaaS Applications
	</p>
	
	<div class="clearfix"></div>
</div>
<?php $this->load->view('help') ?>
<?php $this->load->view('footer') ?>