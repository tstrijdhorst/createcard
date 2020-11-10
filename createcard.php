<?php

use createcard\commands\CreateCommand;
use Symfony\Component\Console\Application;

require_once __DIR__.'/vendor/autoload.php';

$application = new Application();
$application->addCommands(
	[
		new CreateCommand()
	]
);
$application->run();