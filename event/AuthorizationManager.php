<?php
namespace vihv;
/**
 * service
manage information about current user, login and logout
*/
class AuthorizationManager {
	
	const ANONYMOUS = "Anonymous";

	/**
	@return any unique identificator of user,
	most likely - login or user id from database
	*/
	static function getUniqueId() {
		if(empty($_SESSION['TAuthorizationManager']['UID'])) {
			return self::ANONYMOUS; 
			}
		return $_SESSION['TAuthorizationManager']['UID'];
		}
	/**
	 * set unique identificator of user
	 * @param any $UID in most cases - used id from database, but can be anything
	 */
	static function setUniqueId($UID) {
		$_SESSION['TAuthorizationManager']['UID'] = $UID;
		}

	/**
	 * drop unique id, aka logout
	 */
	static function dropUniqueId() {
		$_SESSION['TAuthorizationManager']['UID'] = null;
		}

	/**
	 * @return boolean true if user uid is set (user is logged)
	 */
	static function isLogged() {
		if(@$_SESSION['TAuthorizationManager']['UID'] != null) {
			return true;
			}
		return false;
		}
	}
