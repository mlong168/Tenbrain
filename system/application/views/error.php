<?php
	$this->load->view('header', array(
		'title'		=> 'Welcome',
		'styles'	=> array('main'),
		'scripts'	=> array('jquery-1.4.4.min', 'jquery-ui-1.8.9.custom.min', 'main'),
	));
?>
<div id="main">
	<p class="error_message">An error occured</p>
	
	<div class="clearfix"></div>
</div>
<?php $this->load->view('help') ?>
<?php $this->load->view('footer') ?>