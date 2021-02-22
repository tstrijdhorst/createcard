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
$application = new Application();
$trello      = new Trello(new Client());

$gitHub      = new GitHub();
$application->addCommands(
	[
		new CreateCardCommand($trello, $gitHub),
	]
);
$application->run();