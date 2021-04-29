<?php

use createcard\commands\CreateCardCommand;
use createcard\system\FileHelper;
use createcard\system\GitHub;
use createcard\system\Trello;
use GuzzleHttp\Client;
use Symfony\Component\Console\Application;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__.'/vendor/autoload.php';

$configAvailable = true;
if (!is_file(FileHelper::getConfigFilePath())) {
	echo 'Could not locate environment file. Please make sure '.FileHelper::getConfigFilePath().' exists and contains the right values.'.PHP_EOL;
	$configAvailable = false;
}

if (!is_file(FileHelper::getTrelloAliasFilePath())) {
	echo 'Could not locate trello aliases file. Please make sure '.FileHelper::getTrelloAliasFilePath().' exists and contains the right values.'.PHP_EOL;
	$configAvailable = false;
}

if (!$configAvailable) {
	exit(1);
}

(new Dotenv())->load(FileHelper::getConfigFilePath());
$trelloAliases = Yaml::parseFile(FileHelper::getTrelloAliasFilePath());

$application = new Application();
$trello      = new Trello(new Client(), $trelloAliases);

$gitHub      = new GitHub();
$application->addCommands(
	[
		new CreateCardCommand($trello, $gitHub),
	]
);
$application->run();