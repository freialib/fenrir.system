<?php namespace fenrir\system;

/**
 * @copyright (c) 2014, freia Team
 * @license BSD-2 <http://freialib.github.io/license.txt>
 * @package freia Library
 */
class CLI implements \hlin\archetype\CLI {

	use \hlin\CLITrait;

	/**
	 * @var array
	 */
	protected $args = null;

	/**
	 * @var resource
	 */
	protected $stderr = null;

	/**
	 * @var resource
	 */
	protected $stdin = null;

	/**
	 * @var static
	 */
	protected static $cached_instance = null;

	/**
	 * @return static
	 */
	static function instance($args) {

		if (static::$cached_instance !== null) {
			return static::$cached_instance;
		}

		$i = new static;

		$i->args = $args;
		$i->parse();

		return static::$cached_instance = $i;
	}

	/**
	 * ...
	 */
	function __destruct() {
		$this->stderr === null or fclose($this->stderr);
		$this->stdin === null or fclose($this->stdin);
	}

		/**
	 * @codeCoverageIgnore
	 * @see http://php.net/manual/en/function.passthru.php
	 */
	function passthru($command, &$return_var = null) {
		passthru($command, $return_var);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://php.net/manual/en/function.system.php
	 * @return string
	 */
	function system($command, &$return_var = null) {
		return system($command, $return_var);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://php.net/manual/en/function.exec.php
	 * @return string
	 */
	function exec($command, array &$output = null, &$return_var = null) {
		return exec($command, $output, $return_var);
	}

	/**
	 * @return array
	 */
	function args() {
		return $this->args;
	}

	/**
	 * ...
	 */
	function printf($format) {
		if ($this->echolevel == 0) {
			$args = func_get_args();
			if (count($args) == 1) {
				echo $args[0];
			}
			else { // more then 1 argument
				echo call_user_func_array('sprintf', $args);
			}
		}
	}

	/**
	 * ...
	 */
	function printf_error($format) {
		# printf_error intentionally ignores echolevel
		$this->open_stderr();
		$bytes = fwrite($this->stderr, call_user_func_array('sprintf', func_get_args()));
		if ($bytes === false) {
			throw new Panic("Failed to write to stderr.");
		}
	}

	/**
	 * @return string
	 */
	function fgets() {
		if ($this->echolevel > 0) {
			throw new Panic('Tried to ask for input in silent mode.');
		}
		$this->open_stdin();
		return trim(fgets($this->stdin), "\n\r\t ");
	}

// ---- Private ---------------------------------------------------------------

	/**
	 * @return static $this
	 */
	protected function parse() {

		$args = $this->args();

		if ($args != null) {
			$this->syscall = array_shift($args);

			if ( ! empty($args)) {
				$this->command = array_shift($args);
			}
			else { // no command
				$this->command = null;
			}

			$this->flags = $args;
		}

		return $this;
	}

	/**
	 * ...
	 */
	protected function open_stderr() {
		if ($this->stderr === null) {
			$this->stderr = fopen('php://stderr', 'w');
			if ($this->stderr === false) {
				throw new Panic("Failed to open stderr.");
			}
		}
	}

	/**
	 * ...
	 */
	protected function open_stdin() {
		if ($this->stdin === null) {
			$this->stdin = fopen('php://stdin', 'r');
			if ($this->stdin === false) {
				throw new Panic("Failed to open stdin.");
			}
		}
	}

} # class
