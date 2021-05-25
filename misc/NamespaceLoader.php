<?php

namespace vihv;

/**
 * this will be kind of singleton, but for namespace only
 * we need it because without caching autoloading take too much time
 */
class NamespaceLoader {
	
	private static $instances = [];
	private $namespace;
	private $debug = false;
	private $folderCache = [];
	private $loadedFiles = [];
	
	public static function getInstance($namespace) {
		if(empty(self::$instances[$namespace])) {
			self::$instances[$namespace] = new NamespaceLoader($namespace);
		}
		return self::$instances[$namespace];
	}
	
	private function __construct($targetNamespace) {
		$this->namespace = $targetNamespace;
	}
	
	/**
	 * @return string
	 */
	public function getNamespace() {
		return $this->namespace;
	}
	
	/**
	 * @return integer
	 */
	public function getNamaspaceCountPlusOne() {
		$exploded = explode("\\", $this->getNamespace());
		return count($exploded)+1;
	}
	
	/**
	 * @param string $classNameWithNamespace class name with namespace like vihv\Control
	 * @param string $folder folder to search for the class
	 */
	public function requireOnce($classNameWithNamespace, $folder) {
		$exploded = explode("\\", $classNameWithNamespace);
		if(count($exploded) != $this->getNamaspaceCountPlusOne())  {
			return;
		}
		$className = end($exploded);
		unset($exploded[count($exploded)-1]);
		
		$namespace = implode("\\",$exploded);
		if($namespace != $this->getNamespace()) {
			return;
		}
		
		$filename = $className.".php";
		$this->searchAndRequire($filename, $folder);
	}
	
	/**
	 * @param string $filename filename
	 * @param string $folder folder to search in
	 */
	public function searchAndRequire($filename, $folder) {
		if(in_array($filename, $this->loadedFiles)) {
			return;
		}
		if(file_exists($folder."/".$filename)) {
//			if($this->debug) {
//				$this->list[] = $folder."/".$filename;
//			}
//			var_dump($folder."/".$filename);
			require_once $folder."/".$filename;
			$this->loadedFiles[] = $filename;
			return;
		}
//		var_dump($this->folderCache);
		if(empty($this->folderCache[$folder])) {
			$this->folderCache[$folder] = File::getChildFolders($folder, false);
		}
		$subfolders = $this->folderCache[$folder];
		foreach($subfolders as $subfolder) {
			$this->searchAndRequire($filename, $subfolder);
		}
	}
}
