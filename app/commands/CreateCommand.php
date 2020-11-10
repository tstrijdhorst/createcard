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
		$this->addArgument('title', InputArgument::REQUIRED, 'The title of your card / PR');
	}
	
	protected function execute(InputInterface $input, OutputInterface $output) {
		$response      = $this->httpClient->post(
			'https://api.trello.com/1/cards',
			[
				'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN']],
				'json'  => [
					'name'      => $input->getArgument('title'),
					'idList'    => $_ENV['TRELLO_LIST_ID_REVIEW'],
					'idMembers' => $_ENV['TRELLO_MEMBER_ID'],
				],
			]
		);
		$response      = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		$trelloCardUrl = $response['shortUrl'];
		
		echo $trelloCardUrl;
		
		return Command::SUCCESS;
	}
}