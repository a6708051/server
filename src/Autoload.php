<?php
namespace server;

class Autoload {
	private $directory;
	private $prefix;
	private $prefixLength;

	public function __construct($baseDirectory = '')
	{
		$this->directory = empty($baseDirectory) ? dirname(__FILE__) : $baseDirectory;
		$this->prefix = __NAMESPACE__.'\\';
		$this->prefixLength = strlen($this->prefix);
	}

	public static function register($prepend = false)
	{
		spl_autoload_register(array(new self(), 'autoload'), true, $prepend);
	}

	public function autoload($className)
	{
		if (0 === strpos($className, $this->prefix)) {
			$parts = explode('\\', substr($className, $this->prefixLength));
			$file_path = $this->directory.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $parts).'.php';
			if (is_file($file_path)) {
				require $file_path;
			}
		}
	}

}