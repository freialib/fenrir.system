<?php namespace fenrir\system;

/**
 * @copyright (c) 2014, freia Team
 * @license BSD-2 <http://freialib.github.io/license.txt>
 * @package freia Library
 */
class Filesystem implements \hlin\archetype\Filesystem {

	use \hlin\FilesystemTrait;

	/**
	 * @return static
	 */
	static function instance() {
		return new static;
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.file-exists.php
	 * @return boolean
	 */
	function file_exists($filename) {
		return file_exists($filename);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.unlink.php
	 * @return boolean
	 */
	function unlink($filename) {
		return unlink($filename);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.chmod.php
	 * @return boolean
	 */
	function chmod($filename, $mode) {
		return chmod($filename, $mode);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.copy.php
	 * @return boolean
	 */
	function copy($source, $dest) {
		return copy($source, $dest);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.dirname.php
	 * @return string
	 */
	function dirname($path) {
		return dirname($path);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.file-get-contents.php
	 * @return string
	 */
	function file_get_contents($filename) {
		return file_get_contents($filename);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.file-put-contents.php
	 * @return boolean
	 */
	function file_put_contents($filename, $data) {
		return file_put_contents($filename, $data) !== false;
	}

	/**
	 * FILE_APPEND version of file_put_contents
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.file-put-contents.php
	 * @return boolean
	 */
	function file_append_contents($filename, $data) {
		return file_put_contents($filename, $data, FILE_APPEND) !== false;
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.filemtime.php
	 * @return int
	 */
	function filemtime($filename) {
		return filemtime($filename);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.filesize.php
	 * @return int
	 */
	function filesize($filename) {
		return filesize($filename);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.filetype.php
	 * @return string fifo, char, dir, block, link, file, socket and unknown
	 */
	function filetype($filename) {
		return filetype($filename);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.is-dir.php
	 * @return boolean
	 */
	function is_dir($filename) {
		return is_dir($filename);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.is-readable.php
	 * @return boolean
	 */
	function is_readable($filename) {
		return is_readable($filename);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.is-writable.php
	 * @return boolean
	 */
	function is_writable($filename) {
		return is_writable($filename);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.is-file.php
	 * @return boolean
	 */
	function is_file($filename) {
		return is_file($filename);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.mkdir.php
	 * @return boolean
	 */
	function mkdir($filename, $mode, $recursive = true) {
		if (is_string($mode) || ! is_numeric($mode)) {
			throw new Panic("Invalid mode specified.");
		}
		return mkdir($filename, $mode, $recursive);;
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.realpath.php
	 * @return string
	 */
	function realpath($path) {
		return realpath($path);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.rename.php
	 * @return boolean
	 */
	function rename($oldname, $newname) {
		return rename($oldname, $newname);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.rmdir.php
	 * @return boolean
	 */
	function rmdir($dirname) {
		return rmdir($dirname);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.touch.php
	 * @return boolean
	 */
	function touch($filename, $time = null) {
		return $time == null ? touch($filename) : touch($filename, $time);
	}

	/**
	 * @codeCoverageIgnore
	 * @see http://www.php.net//manual/en/function.scandir.php
	 * @return array
	 */
	function scandir($directory, $sorting_order = SCANDIR_SORT_ASCENDING) {
		return scandir($directory, $sorting_order);
	}

	/**
	 * GLOB_MARK - Adds a slash to each directory returned
	 * GLOB_NOSORT - Return files as they appear in the directory (no sorting)
	 * GLOB_NOCHECK - Return the search pattern if no files matching it were found
	 * GLOB_NOESCAPE - Backslashes do not quote metacharacters
	 * GLOB_BRACE - Expands {a,b,c} to match 'a', 'b', or 'c'
	 * GLOB_ONLYDIR - Return only directory entries which match the pattern
	 * GLOB_ERR - Stop on read errors (like unreadable directories), by default errors are ignored.
	 *
	 * @codeCoverageIgnore
	 * @see http://www.php.net/manual/en/function.glob.php
	 * @return array
	 */
	function glob($pattern, $flags = 0) {
		return glob($pattern, $flags);
	}

	/**
	 * @see http://www.php.net/manual/en/function.touch.php
	 * @return string
	 */
	function basename($path, $suffix = null) {
		return basename($path, $suffix);
	}

} # class
