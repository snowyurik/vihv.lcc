<?php

namespace vihv;

require_once 'vihv/misc/NamespaceLoader.php';
require_once "vihv/misc/File.php";

/**
 * for autoloading. Locates file for required class and namespace.
 * @examlple
 * 
 * spl_autoload_register(function($className) {
 *	$locator = new \vihv\Locator('my\module');
 *	$locator->requireOnce($className, "my/module/base/folder");
 *	});
 */
class Locator {

	/**
	 * @var string
	 */
	private $namespace;
	private $debug = false;
	private $list = [];
	
	/**
	 * @param string $targetNamespace target namespace
	 */
	public function __construct($targetNamespace, $debug = false) {
		$this->namespaceLoader = NamespaceLoader::getInstance($targetNamespace);
//		$this->namespace = $targetNamespace;
//		$this->debug = $debug;
	}
	
	public function getLoadedFiles() {
		return $this->list;
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
		$this->namespaceLoader->requireOnce($classNameWithNamespace, $folder);
	}
	
	public function _requireOnce($classNameWithNamespace, $folder) {
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
		if(file_exists($folder."/".$filename)) {
			if($this->debug) {
				$this->list[] = $folder."/".$filename;
			}
			require_once $folder."/".$filename;
			return;
		}
		$subfolders = File::getChildFolders($folder);
		foreach($subfolders as $subfolder) {
			$this->searchAndRequire($filename, $subfolder);
		}
	}
}
