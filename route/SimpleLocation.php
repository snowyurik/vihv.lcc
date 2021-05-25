<?php

namespace vihv;

class SimpleLocation implements ILocation {
	
	private $path;
	
	public function __construct($path) {
		$this->path = $path;
	}
    
    public function getPath() {
        return $this->path;
    }

	public function current() {
		return trim(\vihv\Url::getLocalPath(),'/') == $this->getPath();
	}

}
