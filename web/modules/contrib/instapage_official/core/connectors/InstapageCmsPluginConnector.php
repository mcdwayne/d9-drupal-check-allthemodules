<?php

/**
 * Main connector class, used to integrate with PHP-based CMSes. It's job is to detect a CMS that executes the code and select proper CMS Connector.
 */
class InstapageCmsPluginConnector {

  /**
   * @var object $selectedConnector Proper connector for current CMS.
   */
  private static $selectedConnector = null;

  /**
   * @var string $selectedLanguage Language of current CMS. Currently only english is supported.
   */
  private static $selectedLanguage = 'en-GB';

  /**
   * @var array $languageArray An array with proper dictionary.
   */
  private static $languageArray = null;

  /**
   * Gets selected language.
   */
  public static function getSelectedLanguage() {
    return self::$selectedLanguage;
  }

  /**
   * Checks if WordPress is a currently used CMS.
   *
   * @return boolean
   */
  public static function isWP() {

    if (defined('ABSPATH') && defined('WPINC') && defined('WP_CONTENT_DIR')) {
      return true;
    }

    return false;
  }

  /**
   * Checks if Drupal 8 is a currently used CMS.
   *
   * @return boolean
   */
  public static function isDrupal8() {

    if (class_exists('\Drupal\Core\DrupalKernel')) {
      return true;
    }

    return false;
  }

  /**
   * Checks if Drupal 7 is a currently used CMS.
   *
   * @return boolean
   */
  public static function isDrupal7() {

    if (defined('VERSION') && VERSION > 7.0 && VERSION < 8.0) {
      return true;
    }

    return false;
  }

  /**
   * Selects a proper connector based on currently used CMS.
   *
   * @return object Selected connector.
   */
  public static function getSelectedConnector() {

    if (self::$selectedConnector === null) {

      switch (true) {
        case self::isWP():
          require_once(INSTAPAGE_PLUGIN_PATH . '/connectors/InstapageCmsPluginWPConnector.php');
          self::$selectedConnector = new InstapageCmsPluginWPConnector();
        break;

        case self::isDrupal7():
          require_once(INSTAPAGE_PLUGIN_PATH . '/connectors/InstapageCmsPluginDrupal7Connector.php');
          self::$selectedConnector = new InstapageCmsPluginDrupal7Connector();
        break;

        case self::isDrupal8():
          require_once(INSTAPAGE_PLUGIN_PATH . '/connectors/InstapageCmsPluginDrupal8Connector.php');
          self::$selectedConnector = new InstapageCmsPluginDrupal8Connector();
        break;

        default:
          die('Unsupported CMS');
      }
    }

    return self::$selectedConnector;
  }

  /**
   * Gets the plugin's directory name in current CMS.
   *
   * @return string Directory name.
   */
  public static function getPluginDirectoryName() {
    return self::getSelectedConnector()->getPluginDirectoryName();
  }

  /**
   * Gets the current user access rights to the Instapage plugin.
   *
   * @return bool Returns true if user can manage the Instapage plugin.
   */
  public static function currentUserCanManage() {
    return self::getSelectedConnector()->currentUserCanManage();
  }

  /**
   * Gets the value of language variable.
   */
  public static function lang() {
    return self::getSelectedConnector()->lang(func_get_args());
  }

  /**
   * Gets the sitemane.
   *
   * @param bool $sanitized If the name should be sanitized.
   *
   * @return string Sitename, sanitized or not.
   */
  public static function getSitename($sanitized = false) {
    return self::getSelectedConnector()->getSitename($sanitized);
  }

  /**
   * Gets the site base URL.
   *
   * @param bool $protocol Value returned with protocol or not.
   *
   * @return string Site base URL. With protocol or not.
   */
  public static function getSiteURL($protocol = true) {
    return self::getSelectedConnector()->getSiteURL($protocol);
  }

  /**
   * Gets the site home URL.
   *
   * @param bool $protocol Value returned with protocol or not.
   *
   * @return string Site home URL. With protocol or not.
   */
  public static function getHomeURL($protocol = true) {
    return self::getSelectedConnector()->getHomeURL($protocol);
  }

  /**
   * Gets the currently used CMS name.
   *
   * @return string CMS name.
   */
  public static function getCMSName() {
    return self::getSelectedConnector()->getCMSName();
  }

  /**
   * Sends an e-mail using CMS native email sending method.
   *
   * @param string $to Receiver address.
   * @param string $subject A subject.
   * @param string $message A message.
   * @param string $headers Message headers. Default: ''.
   * @param aray $attachments Attachments.
   *
   * @return bool True on success.
   */
  public static function mail($to, $subject, $message, $headers = '', $attachments = array()) {
    return self::getSelectedConnector()->mail($to, $subject, $message, $headers, $attachments);
  }

  /**
   * Gets the AJAX URL for currently used CMS.
   *
   * @return string AJAX URL.
   */
  public static function getAjaxURL() {
    return self::getSelectedConnector()->getAjaxURL();
  }

  /**
   * Adds a declaration of JS to Instapage plugin's dashboard in admin panel.
   *
   * @param string $handle Name of the script.
   * @param string $file Path to JS file.
   * @param bool $inFooter Can the file be loaded in the footer?
   */
  public static function addAdminJS($handle, $file, $inFooter = false) {
    return self::getSelectedConnector()->addAdminJSaddAdminJS($handle, $file, $inFooter);
  }

  /**
   * Adds a declaration of CSS to Instapage plugin's dashboard in admin panel.
   *
   * @param string $handle Name of the script.
   * @param string $file Path to CSS file.
   */
  public static function addAdminCSS($handle, $file) {
    return self::getSelectedConnector()->addAdminCSS($handle, $file);
  }

  /**
   * Properly escapes the HTML.
   *
   * @param string $html HTML to escape.
   * @return string Escaped HTML.
   */
  public static function escapeHTML($html) {
    return self::getSelectedConnector()->escapeHTML($html);
  }

  /**
   * Checks (and displays) if a landing page should be displayed instead of normal content served by CMS.
   *
   * @param string $type Type of page to check ('page', 'home' or '404').
   * @param string $slug Slug to check. Default: ''.
   */
  public static function checkPage($type, $slug = '') {
    return self::getSelectedConnector()->checkPage($type, $slug);
  }

  /**
   * Checks if there is a need to replace content of CMS with a landing page. Prevents content replacement on admin/login pages.
   *
   * @return bool True if replace is possible.
   */
  public static function isHtmlReplaceNecessary() {
    return self::getSelectedConnector()->isHtmlReplaceNecessary();
  }

  /**
   * Initiates Instapage plugin's DB structure and loads plugin's classes and selected connector.
   */
  public static function initPlugin() {
    $db = InstapageCmsPluginDBModel::getInstance();
    $db->initPluginTables();
    self::getSelectedConnector()->initPlugin();
  }

  /**
   * Removes the plugin.
   */
  public static function removePlugin() {
    return self::getSelectedConnector()->removePlugin();
  }

  /**
   * Gets the slugs of all landing pages stored in the plugin's DB.
   *
   * @return array Stored slugs.
   */
  public static function getLandingPageSlugs() {
    $db = InstapageCmsPluginDBModel::getInstance();
    $sql = 'SELECT id, slug, \'\' AS editUrl FROM ' . $db->pagesTable . ' WHERE type = \'page\' AND slug <> \'\'';
    $results = $db->getResults($sql);

    return $results;
  }

  /**
   * Executes an action requested via AJAX.
   */
  public static function ajaxCallback() {
    ini_set('display_errors',0);
    header('Content-Type: application/json');
    $post = isset($_POST['data']) ? json_decode(urldecode($_POST['data'])) : array();
    $post->data = isset($post->data) ? $post->data : null;

    if (!empty($post->action)) {
      InstapageCmsPluginAjaxController::getInstance()->doAction($post->action, $post->data);
    }

    die();
  }

  /**
   * Gets the settings module, a CMS-dependant part of the Settings page.
   *
   * @return string HTML form with settings for currently used CMS only.
   */
  public static function getSettingsModule() {
    return self::getSelectedConnector()->getSettingsModule();
  }
}
