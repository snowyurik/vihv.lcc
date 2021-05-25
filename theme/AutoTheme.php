<?php

namespace vihv;

require_once "vihv/interface/ITheme.php";

class EAutoTheme extends Exception {}

class AutoTheme implements \vihv\ITheme {
	
	public function __construct($templateFolder) {
		$this->templateFolder = $templateFolder;
	}


	public function getTemplate($controlClassName) {
		if(!empty($this->templateFolder)) {
			$defaultFolderPaths = \vihv\File::searchIncludePathMany($this->templateFolder);
			foreach($defaultFolderPaths as $defaultFolderPath) {
				$re = $this->searchDefault($controlClassName.".xsl", $defaultFolderPath);
				if(!empty($re)) {
					return $re;
				}
			}
		}
		throw new EAutoTheme('Template for '.$controlClassName.' not found, check '.$this->templateFolder);
	}
	
	public function searchDefault($filename, $folder) {
       // var_dump($folder."/".$filename);
		if(file_exists($folder."/".$filename)) {
            //var_dump("exist");
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
