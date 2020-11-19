<?php

use createcard\commands\CreateCardCommand;
use createcard\system\GitHub;
use createcard\system\Trello;
use GuzzleHttp\Client;
use Symfony\Component\Console\Application;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__.'/vendor/autoload.php';

(new Dotenv())->load(__DIR__.'/.env');
$trelloConfig = Yaml::parseFile(__DIR__.'/trello_config.yml');

$application = new Application();
$application->addCommands(
	[
		new CreateCardCommand(new Trello(new Client(), $trelloConfig), new GitHub() )
	]
);
$application->run();