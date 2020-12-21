<?php

namespace createcard\system;

use SRL\Builder;
use SRL\SRL;

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
		
		return $output[0];
	}
	
	/**
	 * @param string $branchName
	 * @return string
	 */
	public function formatPRUrlFromBranchName(string $branchName): string {
		exec('git remote -v', $output);
		
		$query   = SRL::startsWith()
		              ->literally("origin\tgit@github.com:")
		              ->capture(
			              function (Builder $query) {
				              $query->any()->onceOrMore();
			              }, 'username'
		              )
		              ->literally('/')
		              ->capture(
			              function (Builder $query) {
				              $query->any()->onceOrMore();
			              }, 'repository'
		              )
		              ->literally('.git');
		$matches = $query->getMatches($output[0]);
		
		$username   = $matches[0]->get('username');
		$repository = $matches[0]->get('repository');
		return 'https://github.com/'.$username.'/'.$repository.'/pull/'.$branchName;
	}
}