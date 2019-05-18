<?php

namespace Drupal\chatwee\SSO;

class SsoManager {

	public static function registerUser($parameters) {
    	if(isSet($parameters["login"]) === false) {
    		throw new \Exception("login parameter is required");
    	}

		$userId = SsoUser::register(Array(
			"login" => $parameters["login"],
			"isAdmin" => isSet($parameters["isAdmin"]) === true ? $parameters["isAdmin"] : false,
			"avatar" => isSet($parameters["avatar"]) === true ? $parameters["avatar"] : ""
		));

		return $userId;
	}

    public static function loginUser($parameters) {
		if(isSet($parameters["userId"]) === false) {
			throw new \Exception("userId parameter is required");
		}
    	if(self::isLogged() === true) {
    		self::logoutUser();
    	}

		$sessionId = SsoUser::login(Array(
			"userId" => $parameters["userId"],
			"userIp" => Utils::getUserIp()
		));

		Session::setSessionId($sessionId);

		return $sessionId;
    }

	public static function logoutUser() {
		if(self::isLogged() === false) {
			return false;
		}
		$sessionId = Session::getSessionId();

		SsoUser::removeSession(Array(
			"sessionId" => $sessionId
		));

		Session::clearSessionId();
	}

    public static function editUser($parameters) {
    	if(isSet($parameters["login"]) === false) {
    		throw new \Exception("login parameter is required");
    	}
		if(isSet($parameters["userId"]) === false) {
			throw new \Exception("userId parameter is required");
		}

		$editParameters = Array(
			"userId" => $parameters["userId"],
			"login" => $parameters["login"],
			"avatar" => isSet($parameters["avatar"]) === true ? $parameters["avatar"] : ""
		);

		if(isSet($parameters["isAdmin"]) === true) {
			$editParameters["isAdmin"] = $parameters["isAdmin"];
		}

		SsoUser::edit($editParameters);
    }

    private static function isLogged() {
    	return Session::isSessionSet();
    }
}
