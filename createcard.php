<?php

use createcard\commands\CreateCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__.'/vendor/autoload.php';

(new Dotenv())->load(__DIR__.'/.env');
$trelloConfig = Yaml::parseFile(__DIR__.'/trello_config.yml');

$application = new Application();
$application->addCommands(
	[
		new CreateCommand(new \GuzzleHttp\Client(), $trelloConfig)
	]
);
$application->run();