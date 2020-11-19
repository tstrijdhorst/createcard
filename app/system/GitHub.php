<?php

namespace createcard\system;

class GitHub {
	/**
	 * @param string $title
	 * @param string $body
	 * @return string url to the created PR
	 * @throws \Exception
	 */
	public function createPR(string $title, string $body): string {
		exec('gh pr create --title '.escapeshellarg($title).' --body '.escapeshellarg($body).' 2>&1', $output, $exitCode);
		
		if ($exitCode !== 0) {
			throw new \Exception('Something went wrong while trying to make the PR. Output of the command: '.PHP_EOL.implode(PHP_EOL, $output));
		}
		
		return $output[0];
	}
}