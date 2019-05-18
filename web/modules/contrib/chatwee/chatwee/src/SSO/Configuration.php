<?php

namespace Drupal\chatwee\SSO;

class Configuration
{
	private static $_chatId = null;

	private static $_clientKey = null;

	private static $_customUserAgent = null;

	public static function setChatId($chatId) {
		self::$_chatId = $chatId;
	}

	public static function setClientKey($clientKey) {
		self::$_clientKey = $clientKey;
	}

	public static function setCustomUserAgent($customUserAgent) {
		self::$_customUserAgent = $customUserAgent;
	}

	public static function getChatId() {
		return self::$_chatId;
	}

	public static function getClientKey() {
		return self::$_clientKey;
	}

	public static function getCustomUserAgent() {
		return self::$_customUserAgent;
	}

	public static function isConfigurationSet() {
		return self::$_chatId !== null && self::$_clientKey !== null;
	}
}
