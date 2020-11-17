<?php

namespace createcard\commands;

use createcard\system\GitHub;
use createcard\system\Trello;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReviewCommand extends Command {
	protected static $defaultName = 'review';
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
		$this->addArgument('title', InputArgument::REQUIRED, 'The title of your card / PR');
		$this->addOption('label', 'l', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Labels to add to the card');
		$this->addOption('member', 'm', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Members to assign to the card');
		$this->addOption('reviewer', 'r', InputOption::VALUE_REQUIRED, 'Member to assign as reviewer');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$title    = $input->getArgument('title');
		$labels   = $input->getOption('label');
		$members  = $input->getOption('member');
		$reviewer = $input->getOption('reviewer');
		
		[$trelloCardUrl, $trelloCardID] = $this->trello->createCard('review', $title, $labels, $members);
		try {
			$githubPRUrl = $this->gitHub->createPR($title, $trelloCardUrl);
		}
		catch (\Exception $e) {
			$this->trello->deleteCard($trelloCardID);
			throw $e;
		}
		
		$this->trello->attachUrlToCard($trelloCardID, $githubPRUrl);
		
		if ($reviewer !== null) {
			$this->trello->assignReviewer($trelloCardID, $reviewer);
		}
		
		$output->writeln('Card URL: '.$trelloCardUrl.PHP_EOL.'PR URL: '.$githubPRUrl);
		
		return Command::SUCCESS;
	}
}