<?php
	$this->load->view('header', array(
		'title'		=> 'Welcome',
		'styles'	=> array('main', 'account'),
		'scripts'	=> array('jquery-1.4.4.min', 'jquery-ui-1.8.9.custom.min', 'main'),
	));
?>
<div class="main small_content">
	<p class="selection_message wide">Choose one of the options below:</p>
	<div class="selections">
	<?php foreach($selections as $class => $data): ?>
		<a class="selection_item<?php echo $data['disabled'] ? ' disabled' : '' ?>"
			href="<?php echo base_url() . ($data['disabled'] ? 'apology' : $next . '/' . $class) ?>">
			<span class="selection_image <?php echo $type . ' ' . $class ?>">.</span>
			<span class="control selection_button <?php echo $data['type'] ?>"><?php echo $data['text'] ?></span>
		</a>
	<?php endforeach; ?>
	</div>
	<div class="clearfix"></div>
</div>
<?php $this->load->view('help') ?>
<?php $this->load->view('footer') ?>