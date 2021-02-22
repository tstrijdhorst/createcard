<?php

namespace createcard\system;

use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

class Trello {
	private Client $httpClient;
	
	private const API_BASE_URL = 'https://api.trello.com/1';
	
	public function __construct(Client $httpClient) {
		$this->httpClient = $httpClient;
	}
	
	/**
	 * @param string $listName
	 * @param string $title
	 * @param string $description
	 * @param array  $labelNames
	 * @param array  $memberNames
	 * @return array [url, id]
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 * @throws \JsonException
	 */
	public function createCard(string $listName, string $title, string $description = '', array $labelNames = [], array $memberNames = []): array {
		$memberIds = array_map(
			function ($name) {
				return $this->getMemberIdByUsernameOrAlias($name);
			}, $memberNames
		);
		
		$labelIds = array_map(
			function ($name) {
				return $this->getLabelIdByNameOrAlias($name);
			}, $labelNames
		);
		
		//Add the creator of this card as the first member
		array_unshift($memberIds, $_ENV['TRELLO_MEMBER_ID']);
		
		$response = $this->httpClient->post(
			self::API_BASE_URL.'/cards',
			[
				'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN']],
				'json'  => [
					'name'      => $title,
					'desc'      => $description,
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
		$reviewerId = $this->getMemberIdByUsernameOrAlias($reviewerName);
		
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
	
	public function getBoardMembers(string $boardId): array {
		$response = $this->httpClient->get(
			self::API_BASE_URL."/boards/{$boardId}/memberships",
			[
				'query' => [
					'key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN'], 'member' => 'true', 'member_fields' => ['username']],
			]
		);
		
		$boardInfo = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		
		return array_reduce(
			$boardInfo, function (array $carry, array $memberInfo) {
			return array_merge($carry, [$memberInfo['member']['username'] => $memberInfo['member']['id']]);
		}, []
		);
	}
	
	private function isMemberOfCard(string $cardId, string $memberId): bool {
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
	
	/**
	 * @param string $usernameOrAlias
	 * @return string
	 * @throws \JsonException
	 */
	public function getMemberIdByUsernameOrAlias(string $usernameOrAlias): string {
		$aliases = Yaml::parseFile(__DIR__.'/../../trello_alias.yml');
		
		if (isset($aliases['members'][$usernameOrAlias])) {
			$usernameOrAlias = $aliases['members'][$usernameOrAlias];
		}
		
		$memberIds = $this->getBoardMembers($_SERVER['TRELLO_BOARD_ID']);
		
		if (!isset($memberIds[$usernameOrAlias])) {
			throw new \Exception('Username or alias not found: '.$usernameOrAlias);
		}
		
		return $memberIds[$usernameOrAlias];
	}
	
	public function getLabelIdByNameOrAlias(string $labelNameOrAlias) : string {
		$aliases = Yaml::parseFile(__DIR__.'/../../trello_alias.yml');
		
		if (isset($aliases['labels'][$labelNameOrAlias])) {
			$labelNameOrAlias = $aliases['labels'][$labelNameOrAlias];
		}
		
		$labelNameOrAlias = strtolower($labelNameOrAlias);
		
		$labelIds = $this->getBoardLabels($_SERVER['TRELLO_BOARD_ID']);
		
		if (!isset($labelIds[$labelNameOrAlias])) {
			throw new \Exception('Labelname or alias not found: '.$labelNameOrAlias);
		}
		
		return $labelIds[$labelNameOrAlias];
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
	
	/**
	 * @param string $boardId
	 * @return array [labelName => id], @note labelName has been made lowercase
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 * @throws \JsonException
	 */
	private function getBoardLabels(string $boardId) {
		$response = $this->httpClient->get(
			self::API_BASE_URL."/boards/{$boardId}",
			[
				'query' => [
					'key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN'],
					'labels' => 'all', 'label_fields' => ['name', 'id']
				],
			]
		);
		
		$boardInfo = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		
		return array_reduce(
			$boardInfo['labels'], function (array $carry, array $label) {
			return array_merge($carry, [strtolower($label['name']) => $label['id']]);
		}, []
		);
	}
}