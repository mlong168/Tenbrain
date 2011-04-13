<?php
	$this->load->view('header', array(
		'title'		=> 'Welcome',
		'styles'	=> array('main', 'account'),
		'scripts'	=> array('jquery-1.4.4.min', 'jquery-ui-1.8.9.custom.min', 'main'),
	));
?>
<div class="main small_content">
	<p class="selection_message wide">You have selected:</p>
	<div class="results">
	<?php foreach($results as $class => $text): ?>
		<div class="selected_item">
			<span class="selection_image <?php echo $class?>">.</span>
			<div class="selection_text final"><?php echo str_replace('<br />', ' ', $text) ?></div>
		</div>
		<?php if($text !== end($results)): ?>
			<div class="control arrow"></div>
		<?php endif; ?>
	<?php endforeach; ?>	
	<div class="clearfix"></div>
	</div>
	
	<div class="confirm_dialogue">
		<a class="control reject_button small" href="<?php echo base_url() ?>">Change this</a>
		<a class="control selection_button small" href="/selection/confirm">Confirm</a>
	</div>
	
	<div class="clearfix"></div>
</div>
<?php $this->load->view('help') ?>
<?php $this->load->view('footer') ?>