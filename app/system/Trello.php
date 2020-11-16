<?php

namespace createcard\system;

use GuzzleHttp\Client;

class Trello {
	private Client $httpClient;
	private array  $config;
	
	private const API_BASE_URL = 'https://api.trello.com/1';
	
	public function __construct(Client $httpClient, array $config) {
		$this->httpClient = $httpClient;
		$this->config     = $config;
	}
	
	/**
	 * @param string $title
	 * @param string $listName
	 * @return array
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 * @throws \JsonException
	 */
	public function createCard(string $title, string $listName) {
		$response = $this->httpClient->post(
			self::API_BASE_URL.'/cards',
			[
				'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN']],
				'json'  => [
					'name'      => $title,
					'idList'    => $this->config['lists'][($listName)],
					'idMembers' => $this->config['members']['me'],
				],
			]
		);
		
		$response = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		
		return [$response['url'], $response['id']];
	}
	
	public function attachUrlToCard(string $id, string $url): void {
		$this->httpClient->post(
			"https://api.trello.com/1/cards/{$id}/attachments",
			[
				'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN']],
				'json'  => [
					'url' => $url,
				],
			]
		);
	}
	
	public function deleteCard(string $id): void {
		$this->httpClient->delete(
			"https://api.trello.com/1/cards/{$id}",
			[
				'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN']],
			]
		);
	}
}