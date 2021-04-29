<?php

namespace createcard\system;

class FileHelper {
	private const CONFIG_DIR_NAME = '.config/createcard'; // relative to homedir
	
	public static function getConfigFilePath(): string {
		return self::getHomeDir().'/'.self::CONFIG_DIR_NAME.'/.env';
	}
	
	public static function getTrelloAliasFilePath(): string {
		return self::getHomeDir().'/'.self::CONFIG_DIR_NAME.'/trello_alias.yml';
	}
	
	private static function getHomeDir(): string {
		return posix_getpwuid(posix_geteuid())['dir'];
	}
}
