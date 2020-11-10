<?php

use createcard\commands\CreateCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__.'/vendor/autoload.php';

(new Dotenv())->load(__DIR__.'/.env');

$application = new Application();
$application->addCommands(
	[
		new CreateCommand(new \GuzzleHttp\Client())
	]
);
$application->run();