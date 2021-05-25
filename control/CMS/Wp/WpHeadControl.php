<?php
namespace vihv;
require_once "vihv/model/CMS/Wp/WordpressWrapper.php";

class WpHeadControl extends Control {
	const DEFAULT_TEMPLATE = "vihv/design/Wp/WpHeadControl.xsl";

	public function onParkedEvent($sender) {
		$sender->Enable();
	}

	public function onEnableEvent($sender) {
		ob_start();
		wp_head();
		$sender->setData([
			'wphead' => ob_get_clean(),
			'template_url' => get_bloginfo('template_url'),
			'siteurl' => WordpressWrapper::getSiteUrl(),
			'title' => WordpressWrapper::getSiteTitle(),
			'headtitle' => WordpressWrapper::getHeadTitle(),
			'favicon' => WordpressWrapper::getFavicon(),
		]);
		if(class_exists('WpThemeColors')) {
			$options = array();
			foreach(WpThemeColors::getColors() as $color) {
				$options[] = $color['name']."=".urlencode(get_theme_mod('vihv_'.$color['name'], $color['default']));
			}
			$sender->pushData('cssoptions',implode('&',$options));
		}
	}
		
	public function getTemplate() {
			try {
				return parent::getTemlate();
			} catch(Exception $e) {
				return self::DEFAULT_TEMPLATE;
			}
		}

	}
