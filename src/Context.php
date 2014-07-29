<?php namespace fenrir\system;

/**
 * @copyright (c) 2014, freia Team
 * @license BSD-2 <http://freialib.github.io/license.txt>
 * @package freia Library
 */
class Context implements \hlin\archetype\Context {

	use \hlin\ContextTrait;

	/**
	 * @return static
	 */
	static function instance(\hlin\archetype\Filesystem $fs = null, \hlin\archetype\Logger $logger = null, \hlin\archetype\Configs $configs = null) {
		global $argv;
		static $instance = null;

		if ($instance == null) {
			$i = new static;

			if ($fs === null) {
				$fs = \fenrir\Filesystem::instance();
			}

			if ($logger === null) {
				$logger = \hlin\NoopLogger::instance();
			}

			if ($configs !== null) {
				$i->confs_is($configs);
			}

			$i->logger_is($logger);
			$i->filesystem_is($fs);
			$i->cli_is(\fenrir\CLI::instance($argv));
			$i->web_is(\fenrir\Web::instance());
			return $instance = $i;
		}

		return $instance;
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://php.net/manual/en/function.php-sapi-name.php
	 * @return string
	 */
	function php_sapi_name() {
		return php_sapi_name();
	}

} # class
