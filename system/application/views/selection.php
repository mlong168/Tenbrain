<?php
	$this->load->view('header', array(
		'title'		=> 'Welcome',
		'styles'	=> array('main'),
		'scripts'	=> array('jquery-1.4.4.min', 'jquery-ui-1.8.9.custom.min', 'main'),
	));
?>

<div id="main">
	<p class="selection_message">Choose one of the options below:</p>
	<div class="selections">
		<a class="selection_item" href="g">
			<span class="tenstack business">.</span>
			<span class="control selection_button">Business<br />Application</span>
		</a>
		<a class="selection_item" href="h">
			<span class="tenstack enterprise">.</span>
			<span class="control selection_button">Enterprise<br />Application</span>
		</a>
		<a class="selection_item" href="k">
			<span class="tenstack web">.</span>
			<span class="control selection_button">Web<br />Application</span>
		</a>
	</div>
	<div class="control helper"></div>
	<div class="clearfix"></div>
</div>

<?php $this->load->view('footer') ?>