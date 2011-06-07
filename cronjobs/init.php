<?php
	$time = microtime(true);
	$memory = memory_get_usage();
	 
	require '/home/yvershynin/tenbrain/application/Bootstrap.php';
	 
	Bootstrap::setupEnvironment();
	 
	Bootstrap::setupRegistry();
	Bootstrap::setupConfiguration();
	 
	Bootstrap::setupDatabase();
	Bootstrap::setupTranslation();
	 
	register_shutdown_function('__shutdown');
	 
	function __shutdown() {
		global $time, $memory;
		$endTime = microtime(true);
		$endMemory = memory_get_usage();
	 
		echo '
		Time [' . ($endTime - $time) . '] Memory [' . number_format(( $endMemory - $memory) / 1024) . 'Kb]';
	}
	

