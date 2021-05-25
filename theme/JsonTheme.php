<?php

namespace vihv;

require_once "vihv/interface/ITheme.php";


/**
 * basic new \vihv\JsonTheme('config/theme.json');
 * typicaly theme configuration stored in config/theme.json
 * example:
 * {
 *    "myNamespace-myControl"	:	"design/myControl.xsl"
 * }
*/
class JsonTheme implements \vihv\ITheme {

	private $filename;
	private $parentThemes;

	function __construct($filename, $parentThemes = []) {
		$this->filename  = $filename;
		$this->parentThemes = $parentThemes;
	}

	/**
	 * @return json object from config file
	 */
	private function getTheme() {
		$contents = file_get_contents($this->filename, true);
		if($contents === false) {
			throw new EJsonTheme('Theme file '.$this->filename.' not found or corrupted');
		}
		$json = json_decode($contents);
		if(empty($json)) {
			throw new EJsonTheme('Theme file '.$this->filename.' is not valid json file');
		}
		return $json;
	}

	public function getTemplate($ControlClassName) {
		$theme = $this->getTheme();
		foreach($theme as $item) {
			if(!empty($theme->$ControlClassName)) {
				$filename = \vihv\File::SearchIncludePath($theme->$ControlClassName);
				if(empty($filename)) {
					$filename = $item['path'].'/'.$theme->$ControlClassName;
				}
				if(!file_exists($filename)) {
					throw new EJsonTheme('Template file '.$filename.' not found');
				}
				return $filename;
			}
		}
		foreach($this->parentThemes as $item) {
			try{
				return $item->getTemplate($ControlClassName);
			}catch(EJsonTheme $e) {}
		}
		if(!empty($theme->defaultFolder)) {
			$defaultFolderPaths = \vihv\File::searchIncludePathMany($theme->defaultFolder);
			foreach($defaultFolderPaths as $defaultFolderPath) {
				$re = $this->searchDefault($ControlClassName.".xsl", $defaultFolderPath);
				if(!empty($re)) {
					return $re;
				}
			}
		}
		throw new EJsonTheme('Template for '.$ControlClassName.' not found, check '.$this->filename);
	}

	public function searchDefault($filename, $folder) {
		if(file_exists($folder."/".$filename)) {
			return $folder."/".$filename;
		}
		$subfolders = File::getChildFolders($folder);
		foreach($subfolders as $subfolder) {
			$re = $this->searchDefault($filename, $subfolder);
			if($re !== false) {
				return $re;
			}
		}
		return false;
	}
}

class EJsonTheme extends Exception {}
