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
$trelloAliases = Yaml::parseFile(__DIR__.'/trello_alias.yml');

$application = new Application();
$trello      = new Trello(new Client(), $trelloAliases);

$gitHub      = new GitHub();
$application->addCommands(
	[
		new CreateCardCommand($trello, $gitHub),
	]
);
$application->run();