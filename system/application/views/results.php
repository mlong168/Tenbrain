<?php
	$this->load->view('header', array(
		'title'		=> 'Welcome',
		'styles'	=> array('main'),
		'scripts'	=> array('jquery-1.4.4.min', 'jquery-ui-1.8.9.custom.min', 'main'),
	));
?>
<div id="main">
	<p class="selection_message">You have selected:</p>
	<div class="selections">
	<?php foreach($results as $class => $text): ?>
		<div class="selected_item">
			<span class="selection_image <?php echo $class?>">.</span>
			<div class="selection_text"><?php echo str_replace('<br />', ' ', $text) ?></div>
		</div>
		<?php if($text !== end($results)): ?>
			<div class="control arrow"></div>
		<?php endif; ?>
	<?php endforeach; ?>	
	<div class="clearfix"></div>
	</div>
	<div class="confirm_dialogue">
		<a class="control reject_button small">Change this</a>
		<a class="control selection_button small">Confirm</a>
	</div>
	<div class="control helper"></div>
	<div class="clearfix"></div>
</div>

<?php $this->load->view('footer') ?>