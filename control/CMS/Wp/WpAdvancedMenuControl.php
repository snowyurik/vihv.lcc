<?php

namespace vihv;

require_once 'vihv/control/CMS/Wp/WpBasicMenuControl.php';
require_once 'vihv/model/CMS/Wp/WpMenu.php';

class WpAdvancedMenuControl extends WpBasicMenuControl {
		
	public function onEnableEvent($sender) {
		$menu = new WpMenu($this->menuLocation);
		$sender->pushData('menu', $menu->getItems());
	}

}
