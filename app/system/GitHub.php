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
		exec('git push', $output, $exitCode);
		
		$output = implode(PHP_EOL, $output);
		
		if ($exitCode !== 0) {
			throw new \Exception('Something went wrong while trying to push to the server. Output of the command: '.PHP_EOL.$output);
		}
		
		exec('gh pr create --title '.escapeshellarg($title).' --body '.escapeshellarg($body).' 2>&1', $output, $exitCode);
		
		$output = implode(PHP_EOL, $output);
		
		if ($exitCode !== 0) {
			throw new \Exception('Something went wrong while trying to make the PR. Output of the command: '.PHP_EOL.$output);
		}
		
		preg_match('%(?<PRUrl>https://github.com/.*/.*/pull/[0-9]+)%', $output, $matches);
		
		if (!isset($matches['PRUrl'])) {
			throw new \Exception('Something went wrong while trying to make the PR. Output of the command: '.PHP_EOL.$output);
		}
		
		return $matches['PRUrl'];
	}
}