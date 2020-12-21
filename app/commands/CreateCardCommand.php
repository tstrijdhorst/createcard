<?php

namespace createcard\commands;

use createcard\system\GitHub;
use createcard\system\Trello;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCardCommand extends Command {
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
		$this->addArgument('list', InputArgument::REQUIRED, 'The list your card will be placed in <doing, review, test&deploy>');
		$this->addArgument('title', InputArgument::REQUIRED, 'The title of your card / PR');
		$this->addOption('label', 'l', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Labels to add to the card');
		$this->addOption('member', 'm', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Members to assign to the card');
		$this->addOption('reviewer', 'r', InputOption::VALUE_REQUIRED, 'Member to assign as reviewer');
		$this->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'Describe what you are trying to do');
		$this->addOption('description-interactive', 'i', InputOption::VALUE_NONE, 'Enter a description interactively via vim');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$list                   = $input->getArgument('list');
		$title                  = $input->getArgument('title');
		$labels                 = $input->getOption('label');
		$members                = $input->getOption('member');
		$reviewer               = $input->getOption('reviewer');
		$description            = $input->getOption('description');
		$descriptionInteractive = $input->getOption('description-interactive');
		
		if ($description !== null && $descriptionInteractive !== null) {
			$output->writeln('Error: cannot set a description interactively when a description has already been passed');
			return Command::FAILURE;
		}
		
		if ($reviewer !== null && $list !== 'review') {
			$output->writeln('Error: cannot set the --reviewer option if list is not set to `review`');
			return Command::FAILURE;
		}
		
		if ($descriptionInteractive) {
			$temporaryFilePath = tempnam(sys_get_temp_dir(), 'cc_desc_');
			exec('vim '.$temporaryFilePath.' > `tty`');
			$description = file_get_contents($temporaryFilePath);
			unlink($temporaryFilePath);
		}
		
		//If no description is passed, initialize it with an empty string to keep the typing right
		$description = $description === null ? '' : $description;
		
		[$trelloCardUrl, $trelloCardID] = $this->trello->createCard($list, $title, $description, $labels, $members);
		try {
			$githubPRUrl = $this->gitHub->createPR($title, $trelloCardUrl);
		}
		catch (\Exception $e) {
			$this->trello->deleteCard($trelloCardID);
			
			if ($descriptionInteractive) {
				$output->writeln('Description from interactive: '.PHP_EOL.$description);
			}
			
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