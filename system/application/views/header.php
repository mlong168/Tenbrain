<?php echo doctype('html4-trans') ?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $title ?> - TenBrain - Save your Brain, While saving Money!</title>
	
	<?php if(count($styles)): ?>
		<?php foreach($styles as $name): ?>
			<link rel="stylesheet" type="text/css" href="<?php echo base_url() . 'css/' . $name; ?>.css" />
		<?php endforeach; ?>
	<?php endif; ?>
	
	<?php if(count($scripts)): ?>
		<?php foreach($scripts as $name): ?>
			<script type="text/javascript" src="<?php echo base_url() . 'js/' . $name; ; ?>.js"></script>
		<?php endforeach; ?>
	<?php endif; ?>
</head>
<body>