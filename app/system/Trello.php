<?php

namespace createcard\system;

use GuzzleHttp\Client;
use Symfony\Component\Yaml\Yaml;

class Trello {
	private Client $httpClient;
	
	private const API_BASE_URL = 'https://api.trello.com/1';
	private array $aliases;
	
	public function __construct(Client $httpClient, array $aliases) {
		$this->httpClient = $httpClient;
		$this->aliases    = $aliases;
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
		
		//Add the creator of this card as the first member
		array_unshift($memberIds, $this->getMemberIdFromToken());
		
		$labelIds = array_map(
			function ($name) {
				return $this->getLabelIdByNameOrAlias($name);
			}, $labelNames
		);
		
		$listId = $this->getListIdByNameOrAlias($listName);
		
		$response = $this->httpClient->post(
			self::API_BASE_URL.'/cards',
			[
				'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN']],
				'json'  => [
					'name'      => $title,
					'desc'      => $description,
					'idList'    => $listId,
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
	
	private function getMemberIdFromToken(): string {
		$response = $this->httpClient->get(
			self::API_BASE_URL."/tokens/{$_ENV['TRELLO_API_TOKEN']}/member",
			[
				'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN'], 'fields' => ['id']],
			]
		);
		
		$response = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		
		return $response['id'];
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
	 * @param string $usernameOrAlias
	 * @return string
	 * @throws \JsonException
	 */
	private function getMemberIdByUsernameOrAlias(string $usernameOrAlias): string {
		$usernameOrAlias = $this->resolveUsernameAlias($usernameOrAlias);
		
		$memberIds = $this->getBoardMembers($_SERVER['TRELLO_BOARD_ID']);
		
		if (!isset($memberIds[$usernameOrAlias])) {
			throw new \Exception('Username or alias not found: '.$usernameOrAlias);
		}
		
		return $memberIds[$usernameOrAlias];
	}
	
	private function getLabelIdByNameOrAlias(string $labelNameOrAlias): string {
		if (isset($this->aliases['labels'][$labelNameOrAlias])) {
			$labelNameOrAlias = $this->aliases['labels'][$labelNameOrAlias];
		}
		
		$labelNameOrAlias = strtolower($labelNameOrAlias);
		
		$labelIds = $this->getBoardLabels($_SERVER['TRELLO_BOARD_ID']);
		
		if (!isset($labelIds[$labelNameOrAlias])) {
			throw new \Exception('Labelname or alias not found: '.$labelNameOrAlias);
		}
		
		return $labelIds[$labelNameOrAlias];
	}
	
	private function getListIdByNameOrAlias(string $listNameOrAlias): string {
		if (isset($this->aliases['labels'][$listNameOrAlias])) {
			$listNameOrAlias = $this->aliases['labels'][$listNameOrAlias];
		}
		
		$listNameOrAlias = strtolower($listNameOrAlias);
		
		$listIds = $this->getBoardLists($_SERVER['TRELLO_BOARD_ID']);
		
		if (!isset($listIds[$listNameOrAlias])) {
			throw new \Exception('Labelname or alias not found: '.$listNameOrAlias);
		}
		
		return $listIds[$listNameOrAlias];
	}
	
	private function getBoardMembers(string $boardId): array {
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
			return array_merge($carry, [strtolower($memberInfo['member']['username']) => $memberInfo['member']['id']]);
		}, []
		);
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
					'key'    => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN'],
					'labels' => 'all', 'label_fields' => ['name', 'id'],
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
	
	private function getBoardLists(string $boardId): array {
		$response = $this->httpClient->get(
			self::API_BASE_URL."/boards/{$boardId}",
			[
				'query' => [
					'key'   => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN'],
					'lists' => 'open', 'list_fields' => ['id', 'name'],
				],
			]
		);
		
		$boardInfo = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
		
		return array_reduce(
			$boardInfo['lists'], function (array $carry, array $listInfo) {
			return array_merge($carry, [strtolower($listInfo['name']) => $listInfo['id']]);
		}, []
		);
	}
	
	/**
	 * @param string $cardId
	 * @param array  $memberNames
	 */
	public function assignFYI(string $cardId, array $memberNames) {
		foreach($memberNames as $memberName) {
			$fyiMemberId = $this->getMemberIdByUsernameOrAlias($memberName);
			if (!$this->isMemberOfCard($cardId, $fyiMemberId)) {
				$this->httpClient->post(
					self::API_BASE_URL."/cards/{$cardId}/idMembers",
					[
						'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN']],
						'json'  => [
							'value' => $fyiMemberId,
						],
					]
				);
			}
		}
		
		$fyiMessage = array_reduce($memberNames, function ($carry, $memberName) {
			return $carry .= '@'.$this->resolveUsernameAlias($memberName).' ';
		}, '').' FYI';
		
		$this->httpClient->post(
			self::API_BASE_URL."/cards/{$cardId}/actions/comments",
			[
				'query' => ['key' => $_ENV['TRELLO_API_KEY'], 'token' => $_ENV['TRELLO_API_TOKEN']],
				'json'  => [
					'text' => $fyiMessage,
				],
			]
		);
	}
	
	/**
	 * @param string $usernameOrAlias
	 * @return mixed|string
	 */
	private function resolveUsernameAlias(string $usernameOrAlias) {
		if (isset($this->aliases['members'][$usernameOrAlias])) {
			$usernameOrAlias = $this->aliases['members'][$usernameOrAlias];
		}
		return $usernameOrAlias;
	}
}