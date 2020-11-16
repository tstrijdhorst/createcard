<?php

namespace createcard\commands;

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateCommand extends Command {
	protected static $defaultName = 'create-card';
	private Client   $httpClient;
	
	public function __construct(Client $httpClient) {
		parent::__construct();
		$this->httpClient = $httpClient;
	}
	
	protected function configure() {
		parent::configure();
		
		$this->setDescription('Creates a trello card and a github pr with the given title and crossconnects the urls');
		$this->addArgument('list', InputArgument::REQUIRED, 'The list of your card <doing, review, test&deploy>');
		$this->addArgument('title', InputArgument::REQUIRED, 'The title of your card / PR');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$title         = $input->getArgument('title');
		$response      = $this->httpClient->post(
			'https://api.trello.com/1/cards',
			[
				'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN']],
				'json'  => [
					'name'      => $title,
					'idList'    => $this->getListId($input->getArgument('list')),
					'idMembers' => $_ENV['TRELLO_MEMBER_ID'],
				],
			]
		);
		$response      = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		$trelloCardUrl = $response['url'];
		$trelloCardID  = $response['id'];
		
		$githubPRUrl = shell_exec('gh pr create --title '.escapeshellarg($title).' --body '.escapeshellarg($trelloCardUrl));
		
		if ($githubPRUrl === null) {
			$this->httpClient->delete(
				"https://api.trello.com/1/cards/{$trelloCardID}",
				[
					'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN']],
				]
			);
			
			throw new \Exception('Something went wrong while trying to make the PR. Output of the command: '.$githubPRUrl);
		}
		
		$this->httpClient->post(
			"https://api.trello.com/1/cards/{$trelloCardID}/attachments",
			[
				'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN']],
				'json'  => [
					'url' => $githubPRUrl,
				],
			]
		);
		
		$output->writeln('Card URL: '.$trelloCardUrl.PHP_EOL.'PR URL: '.$githubPRUrl);
		
		return Command::SUCCESS;
	}
	
	/**
	 * @param string $name
	 * @return string
	 * @throws \Exception
	 */
	private function getListId(string $name) : string {
		switch($name) {
			case 'doing':
				return $_ENV['TRELLO_LIST_ID_DOING'];
			case 'review':
				return $_ENV['TRELLO_LIST_ID_REVIEW'];
			case 'test&deploy':
				return $_ENV['TRELLO_LIST_ID_TEST&DEPLOY'];
		}
		
		throw new \Exception('Unknown list');
	}
}