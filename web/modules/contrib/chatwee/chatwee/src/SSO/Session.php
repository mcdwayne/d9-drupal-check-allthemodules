<?php

namespace Drupal\chatwee\SSO;

class Session
{
	const SESSION_DURATION = 86400;

	private static function getCookieKey() {
		if(Configuration::isConfigurationSet() === false) {
			throw new \Exception("The client credentials are not set");
		}
		return "chatwee-SID-" . Configuration::getChatId();
	}

    public static function getSessionId() {
    	$cookieKey = self::getCookieKey();

    	return isSet($_COOKIE[$cookieKey]) ? $_COOKIE[$cookieKey] : null;
    }

    public static function setSessionId($sessionId) {
		$hostChunks = explode(".", $_SERVER["HTTP_HOST"]);

		$hostChunks = array_slice($hostChunks, -2);

		$cookieDomain = "." . implode(".", $hostChunks);

		setcookie(self::getCookieKey(), $sessionId, time() + self::SESSION_DURATION, "/", $cookieDomain);
    }

    public static function clearSessionId() {
		$hostChunks = explode(".", $_SERVER["HTTP_HOST"]);

		$hostChunks = array_slice($hostChunks, -2);

		$cookieDomain = "." . implode(".", $hostChunks);

		setcookie(self::getCookieKey(), "", time() - 1, "/", $cookieDomain);
    }

    public static function isSessionSet() {
		return Session::getSessionId() !== null;
    }
}
