<?php

namespace vihv;

class WpFooterControl extends Control {
	
	const DEFAULT_TEMPLATE = "vihv/design/Wp/WpFooterControl.xsl";
	
	public function onParkedEvent($sender) {
		$sender->enable();
		}
		
	public function onEnableEvent($sender) {
		ob_start();
		wp_footer();
		$sender->pushData('wpfooter', ob_get_clean());
	}
	
	public function getTemplate() {
			try {
				return parent::GetTemlate();
			} catch(Exception $e) {
				return self::DEFAULT_TEMPLATE;
			}
		}
	
	}
