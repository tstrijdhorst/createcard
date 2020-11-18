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
	 * @param string $listName
	 * @param string $title
	 * @param array  $labelNames
	 * @param array  $memberNames
	 * @return array [url, id]
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 * @throws \JsonException
	 */
	public function createCard(string $listName, string $title, array $labelNames = [], array $memberNames = []): array {
		$memberNames = array_merge(['me'], $memberNames);
		$memberIds   = array_map(
			function ($name) {
				return $this->config['members'][$name];
			}, $memberNames
		);
		
		$labelIds = array_map(
			function ($name) {
				return $this->config['labels'][$name];
			}, $labelNames
		);
		
		$response = $this->httpClient->post(
			self::API_BASE_URL.'/cards',
			[
				'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN']],
				'json'  => [
					'name'      => $title,
					'idList'    => $this->config['lists'][($listName)],
					'idMembers' => $memberIds,
					'idLabels'  => $labelIds,
				],
			]
		);
		
		$response = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		
		return [$response['url'], $response['id']];
	}
	
	public function attachUrlToCard(string $id, string $url): void {
		$this->httpClient->post(
			self::API_BASE_URL."/cards/{$id}/attachments",
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
			self::API_BASE_URL."/cards/{$id}",
			[
				'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN']],
			]
		);
	}
	
	public function assignReviewer($cardId, string $reviewerName): void {
		$reviewerId = $this->config['members'][$reviewerName];
		
		if (!$this->isMemberOfCard($cardId, $reviewerId)) {
			$this->httpClient->post(
				self::API_BASE_URL."/cards/{$cardId}/idMembers",
				[
					'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN']],
					'json'  => [
						'value' => $reviewerId,
					],
				]
			);
		}
		
		$this->httpClient->post(
			self::API_BASE_URL."/cards/{$cardId}/actions/comments",
			[
				'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN']],
				'json'  => [
					'text' => '@'.$this->getUsernameByMemberId($reviewerId).' please review',
				],
			]
		);
	}
	
	private function isMemberOfCard($cardId, $memberId): bool {
		$response = $this->httpClient->get(
			self::API_BASE_URL."/cards/{$cardId}/members",
			[
				'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN'], 'fields' => ['id']],
			]
		);
		
		$members = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		
		foreach ($members as $member) {
			if ($member['id'] === $memberId) {
				return true;
			}
		}
		
		return false;
	}
	
	private function getUsernameByMemberId($id): string {
		$response = $this->httpClient->get(
			self::API_BASE_URL."/members/{$id}",
			[
				'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN'], 'fields' => ['username']],
			]
		);
		
		$response = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		
		return $response['username'];
	}
}