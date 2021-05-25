<?php
namespace vihv;

class LocationControl extends \vihv\Container {
	
	private $locations = [];
	
	/**
	 * @var Location
	 */
	private $currentLocation;
	
	public function addLocation(ILocation $location) {
		$this->locations[] = $location;
	}
	
	public function onGetEvent($sender, $input) {
		if($this->current()) {
			if(!empty($_POST)) {
				return;
			}
			$this->enable();
			$this->onRead($input);
		}
	}
	
	public function onPostEvent($sender, $input) {
		if($this->current()) {
			$this->onWrite($input);
		}
	}


	public function current() {
		foreach($this->locations as $location) {
			if($location->current()) {
				$this->currentLocation = $location;
				return true;
			}
		}
		return false;
	}
	
	public function getCurrentLocation() {
		return $this->currentLocation;
	}


	public function onRead($input) {}
	public function onWrite($input) {}
}
