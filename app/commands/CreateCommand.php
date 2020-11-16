<?php

namespace createcard\commands;

use createcard\system\GitHub;
use createcard\system\Trello;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends Command {
	protected static $defaultName = 'create-card';
	private Trello   $trello;
	private GitHub   $gitHub;
	
	public function __construct(Trello $trello, GitHub $gitHub) {
		parent::__construct();
		$this->trello = $trello;
		$this->gitHub = $gitHub;
	}
	
	protected function configure() {
		parent::configure();
		
		$this->setDescription('Creates a trello card and a github pr with the given title and crossconnects the urls');
		$this->addArgument('list', InputArgument::REQUIRED, 'The list of your card <doing, review, test&deploy>');
		$this->addArgument('title', InputArgument::REQUIRED, 'The title of your card / PR');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$title    = $input->getArgument('title');
		$listName = $input->getArgument('list');
		
		[$trelloCardUrl, $trelloCardID] = $this->trello->createCard($title, $listName);
		try {
			$githubPRUrl = $this->gitHub->createPR($title, $trelloCardUrl);
		}
		catch(\Exception $e) {
			$this->trello->deleteCard($trelloCardID);
			throw $e;
		}
		
		$this->trello->attachUrlToCard($trelloCardID, $githubPRUrl);
		
		$output->writeln('Card URL: '.$trelloCardUrl.PHP_EOL.'PR URL: '.$githubPRUrl);
		
		return Command::SUCCESS;
	}
}