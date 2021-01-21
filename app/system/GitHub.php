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
		exec('git push');
		exec('gh pr create --title '.escapeshellarg($title).' --body '.escapeshellarg($body).' 2>&1', $output, $exitCode);
		
		if ($exitCode !== 0) {
			throw new \Exception('Something went wrong while trying to make the PR. Output of the command: '.PHP_EOL.implode(PHP_EOL, $output));
		}
		
		preg_match('%(?<PRUrl>https://github.com/.*/.*/pull/[0-9]+)%', $output[0], $matches);
		
		return $matches['PRUrl'];
	}
}