<?php

namespace createcard\system;

class GitHub {
	/**
	 * @param string $title
	 * @param string $body
	 * @return string
	 * @throws \Exception
	 */
	public function createPR(string $title, string $body) : string {
		$githubPRUrl = shell_exec('gh pr create --title '.escapeshellarg($title).' --body '.escapeshellarg($body));
		
		if ($githubPRUrl === null) {
			throw new \Exception('Something went wrong while trying to make the PR. Output of the command: '.$githubPRUrl);
		}
		
		return $githubPRUrl;
	}
}