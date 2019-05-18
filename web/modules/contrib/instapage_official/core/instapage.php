<?php

/*
Plugin Name: Instapage Plugin
Description: The best way for WordPress to seamlessly publish landing pages as a natural extension of your website.
Version: 3.1.8
Plugin URI: https://instapage.com/
Author: Instapage
Author URI: https://instapage.com/
License: GPLv2
*/

define('INSTAPAGE_PLUGIN_PATH', dirname(__FILE__));
define('INSTAPAGE_PLUGIN_FILE', __FILE__);

if (!defined('INSTAPAGE_ENTERPRISE_ENDPOINT')) {
  define('INSTAPAGE_ENTERPRISE_ENDPOINT', 'http://pageserve.co');
}

if (!defined('INSTAPAGE_PROXY_ENDPOINT')) {
  define('INSTAPAGE_PROXY_ENDPOINT', 'http://app.instapage.com');
}

if (!defined('INSTAPAGE_APP_ENDPOINT')) {
  define('INSTAPAGE_APP_ENDPOINT', 'http://app.instapage.com/api/plugin');
}

define('INSTAPAGE_SUPPORT_EMAIL', 'help@instapage.com');

require_once(INSTAPAGE_PLUGIN_PATH . '/connectors/InstapageCmsPluginConnector.php');
require_once(INSTAPAGE_PLUGIN_PATH . '/InstapageCmsPluginHelper.php');
require_once(INSTAPAGE_PLUGIN_PATH . '/models/InstapageCmsPluginDBModel.php');
require_once(INSTAPAGE_PLUGIN_PATH . '/models/InstapageCmsPluginAPIModel.php');
require_once(INSTAPAGE_PLUGIN_PATH . '/models/InstapageCmsPluginPageModel.php');
require_once(INSTAPAGE_PLUGIN_PATH . '/models/InstapageCmsPluginServicesModel.php');
require_once(INSTAPAGE_PLUGIN_PATH . '/models/InstapageCmsPluginDebugLogModel.php');
require_once(INSTAPAGE_PLUGIN_PATH . '/models/InstapageCmsPluginViewModel.php');
require_once(INSTAPAGE_PLUGIN_PATH . '/models/InstapageCmsPluginSubaccountModel.php');
require_once(INSTAPAGE_PLUGIN_PATH . '/InstapageCmsPluginAjaxController.php');

InstapageCmsPluginConnector::initPlugin();
