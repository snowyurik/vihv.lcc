<?php

namespace vihv;

class WpBasicMenuControl extends Control {
	
	public $menuLocation;
	
	public function __construct($menuLocation) {
		$this->menuLocation = $menuLocation;
		parent::__construct();
	}
	
	public function getRootTag() {
		return str_replace("\\","-",get_class($this))."_".str_replace("-","_",$this->menuLocation);
	}
	
	public function onParkedEvent($sender) {
		$sender->enable();
	}
		
	public function onEnableEvent($sender) {
		ob_start();
		wp_nav_menu(array('theme_location'=>$this->menuLocation));
		$sender->pushData('menu', ob_get_clean());
		}
	
	}
