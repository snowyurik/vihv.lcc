<?php

namespace vihv;

/**
 * represent site root location aka homepage
 */
class DefaultLocation extends Location implements ILocation {
	
	public function current() {
		return empty(trim(\vihv\Url::getLocalPath(),'/'));
	}

}
