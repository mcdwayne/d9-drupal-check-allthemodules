<?php

/**
 * Drupal 7 hook, registers the plugin permissions.
 *
 * @return array Drupal 7 specific permission array.
 */
function instapage_cms_plugin_permission() {
  return array(
    'administer instapage_cms_plugin settings' => array(
      'title' => t('Administer Instapage settings'),
      'description' => t('Allow users to administer Instapage settings.'),
   )
 );
}

/**
 * Drupal 7 hook. Adds a link to admin menu.
 *
 * @return array Array with new menu items.
 */
function instapage_cms_plugin_menu() {

  $items = array();
  $items['admin/structure/instapage_cms_plugin'] = array(
    'title' => 'Instapage Plugin',
    'description' => 'The best way for Drupal to seamlessly publish landing pages as a natural extension of your website.',
    'page callback' => 'load_instapage_cms_plugin_dashboard',
    'access arguments' => array('administer instapage_cms_plugin settings'),
    'type' => MENU_NORMAL_ITEM
 );

  return $items;
}

/**
 * Drupal 7 hook. Function called to initiate Instapage plugin.
 */
function instapage_cms_plugin_init() {
  if (!menu_get_item($_GET['q'])) {
    InstapageCmsPluginConnector::getSelectedConnector()->check404();
  }
}

/**
 * Drupal 7 hook. Function called when plugin is installed.
 */
function instapage_cms_plugin_install() {
  user_role_grant_permissions(DRUPAL_AUTHENTICATED_RID, array('administer instapage_cms_plugin settings'));
}

/**
 * Drupal 7 hook. Function called when plugin is uninstalled.
 */
function instapage_cms_plugin_uninstall() {
  InstapageCmsPluginConnector::getSelectedConnector()->removePlugin();
}

/**
 * Drupal 7 hook. Function called to load plugin's dashboard.
 */
function load_instapage_cms_plugin_dashboard() {
  $jsDir = base_path() . drupal_get_path('module', InstapageCmsPluginConnector::getPluginDirectoryName()) . '/core/assets/js';
  $knockoutDir = base_path() . drupal_get_path('module', InstapageCmsPluginConnector::getPluginDirectoryName()) . '/core/knockout';
  $languageFile = base_path() . drupal_get_path('module', InstapageCmsPluginConnector::getPluginDirectoryName()) . '/core/assets/lang/' . InstapageCmsPluginConnector::getSelectedLanguage() . '.js';
  $options = array('scope' => 'footer', 'defer' => false, 'preprocess' => 'false');
  drupal_add_js('var INSTAPAGE_AJAXURL = \'' . InstapageCmsPluginConnector::getAjaxURL() . '\';', array('type' => 'inline', 'scope' => 'header'));
  drupal_add_js($languageFile, $options);
  drupal_add_js($jsDir . '/InstapageCmsPluginLang.js', $options);
  drupal_add_js($knockoutDir . '/core/knockout-3.4.0.js', $options);
  drupal_add_js($jsDir . '/knockout-no-conflict.js', $options);
  drupal_add_js($knockoutDir . '/core/knockout.simpleGrid.3.0.js', $options);
  drupal_add_js($jsDir . '/download.js', $options);
  drupal_add_js($jsDir . '/InstapageCmsPluginAjax.js', $options);
  drupal_add_js($knockoutDir . '/view_models/InstapageCmsPluginPagedGridModel.js', $options);
  drupal_add_js($knockoutDir . '/view_models/InstapageCmsPluginEditModel.js', $options);
  drupal_add_js($knockoutDir . '/view_models/InstapageCmsPluginSettingsModel.js', $options);
  drupal_add_js($knockoutDir . '/view_models/InstapageCmsPluginMessagesModel.js', $options);
  drupal_add_js($knockoutDir . '/view_models/InstapageCmsPluginToolbarModel.js', $options);
  drupal_add_js($knockoutDir . '/view_models/InstapageCmsPluginMasterModel.js', $options);

  // UI KIT
  drupal_add_js('https://code.jquery.com/jquery-2.2.4.min.js', $options);
  drupal_add_js($jsDir . '/mrwhite.js', $options);
  drupal_add_js($jsDir . '/dropdowns.js', $options);
  drupal_add_js($jsDir . '/expand-collapse.js', $options);
  drupal_add_js($jsDir . '/input.js', $options);
  drupal_add_js($jsDir . '/jq.hoverintent.js', $options);
  drupal_add_js($jsDir . '/jquery.tmpl.min.js', $options);
  drupal_add_js($jsDir . '/ripple.js', $options);
  drupal_add_js($jsDir . '/select2.min.js', $options);
  drupal_add_js($jsDir . '/snack-bars.js', $options);
  drupal_add_js($jsDir . '/tabs.js', $options);

  $options = array('preprocess' => 'false');
  $cssDir = drupal_get_path('module', InstapageCmsPluginConnector::getPluginDirectoryName()) . '/core/assets/css';
  drupal_add_css($cssDir . '/mrwhite-reset.css', $options);
  drupal_add_css($cssDir . '/mrwhite-ui-kit.css', $options);
  drupal_add_css($cssDir . '/general.css', $options);

  ob_start();
  InstapageCmsPluginConnector::getSelectedConnector()->loadPluginDashboard();
  $contents = ob_get_contents();
  ob_end_clean();

  return $contents;
}

/**
 * Class that utilizes native Drupal 7 functions to perform actions like remote requests and DB operations.
 */
class InstapageCmsPluginDrupal7Connector {

  /**
   * @var string Name of the CMS.
   */
  public $name = 'drupal';

  /**
   * Gets the plugin directory name.
   *
   * @return string Plugin's directory name.
   */
  public function getPluginDirectoryName() {
    return 'instapage_cms_plugin';
  }

  /**
   * Gets the CMS name.
   *
   * @return string CMS name.
   */
  public function getCMSName() {
    return 'Drupal';
  }

  /**
   * Checks if current user can manage the plugin's dashboard.
   *
   * @return bool Tru is current user has the permissions.
   */
  public function currentUserCanManage() {
    return true;
  }

  /**
   * Prepares the function arguments returned by func_get_args function.
   *
   * @param array $args Arguments to prepare, Default: array().
   *
   * @return array Array of function parameters.
   */
  private function prepareFunctionArgs($args = array()) {
    if (isset($args[0]) && is_array($args[0])) {
      $args = $args[0];
    }

    return $args;
  }

  /**
   * Prepares the basic query with proper metadata/tags and base fields.
   *
   * @param string $sql SQL query. %s can be used to output pre-formatted values.
   *
   * @return string SQL query ready to execute in Drupal 7.
   */
  private function prepare($sql) {
    $sql = str_replace(array('\'%s\'', '%s'), '?', $sql);

    return $sql;
  }

  /**
   * Executes a SQL query.
   *
   * @param string $sql SQL to execute. %s can be used to output pre-formatted values. Values for %s can be passed as arguments for this function.
   *
   * @uses InstapageCmsPluginDrupal7Connector::prepare() to change '%s' to '?'.
   *
   * @return bool True if the query is successful. DB error is logged and false if returned otherwise.
   */
  public function query($sql) {
    $args = func_get_args();
    array_shift($args);
    $args = $this->prepareFunctionArgs($args);
    $sql = $this->prepare($sql);

    try {
      db_query($sql, $args);

      return true;
    } catch (Exception $e) {
      $this->logDbError($e, $sql);

      return false;
    }
  }

  /**
   * Gets the last ID of an insert query.
   *
   * @return integer|boolean Last insert ID of false on error.
   */
  public function lastInsertId() {
    $sql = 'SELECT LAST_INSERT_ID() as last_insert_id';
    $result = $this->getRow($sql);

    return isset($result->last_insert_id) ? $result->last_insert_id : false;
  }

  /**
   * Executes the query and returns the first row.
   *
   * @param string $sql SQL to execute. %s can be used to output pre-formatted values. Values for %s can be passed as arguments for this function.
   *
   * @return mixed first row of results of the query.
   */
  public function getRow($sql) {
    $args = func_get_args();
    array_shift($args);
    $args = $this->prepareFunctionArgs($args);
    $sql = $this->prepare($sql);

    try {
      $result = db_query($sql, $args);

      return $result->fetchObject();
    } catch (Exception $e) {
      $this->logDbError($e, $sql);

      return false;
    }
  }

  /**
   * Executes the query and returns a list of results.
   *
   * @param string $sql SQL to execute. %s can be used to output pre-formatted values. Values for %s can be passed as arguments for this function.
   *
   * @return mixed Array of results, false on error.
   */
  public function getResults($sql) {
    $args = func_get_args();
    array_shift($args);
    $args = $this->prepareFunctionArgs($args);
    $sql = $this->prepare($sql);

    try {
      $result = db_query($sql, $args);
      $resultArray = $result->fetchAll(PDO::FETCH_OBJ);

      if (!is_array($resultArray)) {
        return array();
      }

      return $resultArray;
    } catch (Exception $e) {
      $this->logDbError($e, $sql);

      return false;
    }
  }

  /**
   * Gets the DB prefix from CMS configuration.
   *
   * @return string DB prefix.
   */
  public function getDBPrefix() {
    global $databases;

    $connectionKey = Database::getConnection()->getKey();
    $settings = isset($databases[$connectionKey]) ? $databases[$connectionKey] : null;

    if (!$settings) {
      return null;
    }

    if (!isset($settings['prefix']) && is_array($settings)) {
      $settings = array_pop($settings);
    }

    if (isset($settings['prefix']) && is_array($settings['prefix'])) {
      $settings['prefix'] = array_pop($settings['prefix']);
    }

    return isset($settings['prefix']) ? $settings['prefix'] : '';
  }

  /**
   * Gets charset collation from CMS configuration.
   *
   * @return string Database charset collation.
   */
  public function getCharsetCollate() {
    global $databases;

    $connectionKey = Database::getConnection()->getKey();
    $settings = isset($databases[$connectionKey]) ? $databases[$connectionKey] : null;

    if (!$settings) {
      return null;
    }

    if (!isset($settings['collation']) && is_array($settings)) {
      $settings = array_pop($settings);
    }

    if (isset($settings['collation']) && is_array($settings['collation'])) {
      $settings['collation'] = array_pop($settings['collation']);
    }

    $collation = isset($settings['collation']) ? $settings['collation'] : 'utf8mb4_general_ci';

    return 'COLLATE ' . $collation;
  }

  /**
   * Performsremote request in a way specific for Drupal 7.
   *
   * @param string $url URL for the request.
   * @param array $data Data that will be passed in the request.
   * @param array $headers Headers for the request.
   * @param string $method Method of the request. 'POST' or 'GET'.
   *
   * @return array Request result in a form of associative array.
   */
  public function remoteRequest($url, $data, $headers = array(), $method = 'POST') {
    $dataString = '';

    if (!isset($headers['Content-type'])) {
      $headers['Content-Type'] = 'application/x-www-form-urlencoded';
      InstapageCmsPluginHelper::writeDiagnostics($data, 'Request (' . $method . ') data empty. Ping added.');
    }

    if ($method == 'POST' && (!is_array($data) || !count($data))) {
      $data = array('ping' => true);
    }

    $dataString = http_build_query($data, '', '&');

    if ($method == 'GET') {
      $url .= '?' . urldecode($dataString);
      $dataString = '';
    }

    $cookies = @InstapageCmsPluginHelper::getVar($data['cookies'], array());
    $cookieString = '';

    foreach ($cookies as $key => $cookie) {
      $cookieString .= $key . '=' . urlencode($cookie) . ';';
    }

    if ($cookieString) {
      $headers['Cookie'] = $cookieString;
    }

    $options = array(
      'headers' => $headers,
      'method' => $method,
      'data' => $dataString,
      'max_redirects' => 5,
      'timeout' => 45
   );

    $request = drupal_http_request($url, $options);

    if (isset($request->code) && $request->code == 200) {
      return $this->prepareResponse($request);
    } else {
      return array('status' => 'ERROR', 'message' => InstapageCmsPluginConnector::lang('Request failed with status %s.', $request->code));
    }
  }

  /**
   * Performs remote POST request.
   *
   * @uses InstapageCmsPluginDrupal7Connector::remoteRequest().
   *
   * @param string $url URL for the request.
   * @param array $data Data that will be passed in the request.
   * @param array $headers Headers for the request.
   *
   * @return array Request result in a form of associative array.
   */
  public function remotePost($url, $data, $headers = array()) {
    return $this->remoteRequest($url, $data, $headers, 'POST');
  }

  /**
   * Performs remote GET request.
   *
   * @uses InstapageCmsPluginDrupal7Connector::remoteRequest().
   *
   * @param string $url URL for the request.
   * @param array $data Data that will be passed in the request.
   * @param array $headers Headers for the request.
   *
   * @return array Request result in a form of associative array.
   */
  public function remoteGet($url, $data, $headers = array()) {
    return $this->remoteRequest($url, $data, $headers, 'GET');
  }

  /**
   * Gets the site base URL.
   *
   * @param bool $protocol Value returned with protocol or not.
   *
   * @return string Site base URL. With protocol or not.
   */
  public function getSiteURL($protocol = true) {
    $url = $_SERVER['HTTP_HOST'];

    if ($protocol) {
      if (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS'])) {
        $url = 'https://' . $url;
      } else {
        $url = 'http://' . $url;
      }
    }

    return $url;
  }

  /**
   * Gets the site home URL.
   *
   * @param bool $protocol Value returned with protocol or not.
   *
   * @return string Site home URL. With protocol or not.
   */
  public function getHomeURL($protocol = true) {
    $url = $this->getSiteURL($protocol);

    return $url;
  }

  /**
   * Gets the AJAX URL.
   *
   * @return string AJAX URL.
   */
  public function getAjaxURL() {
    return $this->getSiteURL() . '/index.php?action=instapage_ajax_call';
  }

  /**
   * Gets the value of language variable.
   */
  public function lang() {
    $arguments = func_get_arg(0);

    if (!count($arguments)) {
      return null;
    }

    $text = $arguments[0];
    $variables = array_slice($arguments, 1);

    if (!count($variables)) {
      return $text;
    }

    return vsprintf($text, $variables);
  }

  /**
   * Initiates Instapage plugin's DB structure and loads plugin's classes.
   */
  public function initPlugin() {
    $action = InstapageCmsPluginHelper::getVar($_GET['action'], '');

    if ($action == 'instapage_ajax_call') {
      $this->ajaxCallback();
    } else {
      InstapageCmsPluginHelper::writeDiagnostics($_SERVER['REQUEST_URI'], 'Instapage plugin initiated. REQUEST_URI');
      $this->checkProxy();
      $this->checkHomepage();
      $this->checkCustomUrl();
    }
  }

  /**
   * Removes the plugin.
   */
  public function removePlugin() {
    $subaccount = InstapageCmsPluginSubaccountModel::getInstance();
    $db = InstapageCmsPluginDBModel::getInstance();
    $subaccount->disconnectAccountBoundSubaccounts(true);
    $db->removePluginTables();
  }

  /**
   * Loads the plugin dashboard.
   */
  public function loadPluginDashboard() {
    InstapageCmsPluginHelper::loadTemplate('messages');
    InstapageCmsPluginHelper::loadTemplate('toolbar');
    InstapageCmsPluginHelper::loadTemplate('base');
  }

  /**
   * Executes an action requested via AJAX.
   */
  public function ajaxCallback() {
    InstapageCmsPluginConnector::ajaxCallback();
  }

  /**
   * Checks if current URL is login page.
   *
   * @return bool True if current URL is login page.
   */
  public function isLoginPage() {
    $requestUrl = $_SERVER['REQUEST_URI'];

    if (strpos($requestUrl, '/user') === 0 || $_GET['q'] == 'user') {
      return true;
    }

    return false;
  }

  /**
   * Checks if current URL is admin page.
   *
   * @return bool True if current URL is admin page.
   */
  public function isAdmin() {
    $requestUrl = $_SERVER['REQUEST_URI'];

    if (strpos($requestUrl, '/admin') === 0 || $_GET['q'] == 'admin') {
      return true;
    }

    return false;
  }

  /**
   * Checks (and displays) if a landing page should be displayed instead of normal content served by CMS.
   *
   * @param string $type Type of page to check ('page', 'home' or '404').
   * @param string $slug Slug to check. Default: ''.
   */
  public function checkPage($type, $slug = '') {
    $page = InstapageCmsPluginPageModel::getInstance();
    $result = $page->check($type, $slug);

    if (!$result) {
      return;
    }

    if ($type == '404') {
      $page->display($result, 404);
    } else {
      $page->display($result);
    }
  }

  /**
   * Checks (and displays) if a landing page marked as homepage should be displayed instead of normal CMS homepage.
   *
   * @uses InstapageCmsPluginDrupal7Connector::checkPage()
   */
  public function checkHomepage() {
    $homeUrl = str_replace(array('http://', 'https://'), '', rtrim($this->getHomeURL(), '/'));
    $homeUrlSegments = explode('/', $homeUrl);
    $uriSegments = explode('?', $_SERVER['REQUEST_URI']);
    $uriSegments = explode('/', rtrim($uriSegments[0], '/'));

    if (
      (count($uriSegments) !== count($homeUrlSegments)) ||
      (count($homeUrlSegments) > 1 && $homeUrlSegments[1] != $uriSegments[1])
   ) {
      return false;
    }

    $this->checkPage('home');

    return true;
  }

  /**
   * Checks (and displays) if a landing page marked as 404 should be displayed instead of normal CMS 404 page.
   *
   * @uses InstapageCmsPluginDrupal7Connector::checkPage()
   */
  public function check404() {
    $this->checkPage('404');
  }

  /**
   * Checks (and displays) if a landing page hould be displayed instead of normal CMS page under current URL.
   *
   * @uses InstapageCmsPluginDrupal7Connector::checkPage()
   */
  public function checkCustomUrl() {
    $slug = InstapageCmsPluginHelper::extractSlug($this->getHomeURL());

    if ($slug) {
      $this->checkPage('page', $slug);
    }

    return true;
  }

  /**
   * Checks (and processes it) if a lcurrent request should be processes by plugin's proxy.
   */
  public function checkProxy() {
    $services = InstapageCmsPluginServicesModel::getInstance();

    if ($services->isServicesRequest()) {
      try {
        $services->processProxyServices();

        return;
      } catch (Exception $e) {
        echo $e->getMessage();
      }
    }
  }

  /**
   * get list of slugs that can't be used to publish a landing page.
   *
   * @return array List of prohibitted slugs.
   */
  public function getProhibitedSlugs() {
    $result = array_merge($this->getPostSlugs(), InstapageCmsPluginConnector::getLandingPageSlugs());

    return $result;
  }

  /**
   * Gets the HTML for CMS options.
   *
   * @return string HTML to include in the debug log.
   */
  public function getOptionsDebugHTML() {
    return '';
  }

  /**
   * Gets the HTML for CMS plugins/modules.
   *
   * @return string HTML to include in the debug log.
   */
  public function getPluginsDebugHTML() {
    return '';
  }

  /**
   * Gets the sitename from CMS config.
   *
   * @return string Sitename.
   */
  public function getSitename($sanitized = false) {
    $sitename = variable_get('site_name');

    if ($sanitized) {
      return mb_strtolower(str_replace(' ', '-', $sitename));
    }

    return $sitename;
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
  public function mail($to, $subject, $message, $headers = '', $attachments = array()) {
    global $language_object;

    $module = 'instapage_cms_plugin';
    $key = 'custom_email';
    $params['message'] = $message;
    $params['subject'] = $subject;
    $langcode = $language_object->language;
    $send = true;

    return drupal_mail($module, $key, $to, $langcode, $params, NULL, $send);
  }

  /**
   * Gets the landing pages saved in legacy DB structure.
   *
   * @return array List of landing pages from legacy DB structure.
   */
  public function getDeprecatedData() {
    $pages = variable_get('instapage_pages', NULL);
    $results = array();

    foreach ($pages as $key => $slug) {
      $pageObj = new stdClass;
      $pageObj->id = 0;
      $pageObj->landingPageId = $key;
      $pageObj->slug = $slug;
      $pageObj->type = 'page';
      $pageObj->enterprise_url = $pageObj->slug ? InstapageCmsPluginConnector::getHomeURL() . '/' . $pageObj->slug : Connector::getHomeURL();
      $results[] = $pageObj;
    }

    return $results;
  }

  /**
   * Properly escapes the HTML.
   *
   * @param string $html HTML to escape.
   *
   * @return string Escaped HTML.
   */
  public function escapeHTML($html) {
    return htmlspecialchars($html);
  }

  /**
   * Checks if there is a need to replace content of CMS with a landing page. Prevents content replacement on admin/login pages.
   *
   * @return bool True if replace is possible.
   */
  public function isHtmlReplaceNecessary() {
    if ($this->isAdmin() || $this->isLoginPage() || InstapageCmsPluginHelper::isCustomParamPresent()) {
      InstapageCmsPluginHelper::writeDiagnostics('is_admin || isLoginPage || isCustomParamPresent', 'HTML Replace is not necessary');

      return false;
    }

    return true;
  }

  /**
   * Gets the settings module, a CMS-dependant part of the Settings page.
   *
   * @return string HTML form with settings for currently used CMS only.
   */
  public function getSettingsModule() {
    return '';
  }

  /**
   * Logs DB errors.
   *
   * @param object $e Exception object
   * @param string $sql SQL query.
   */
  private function logDbError($e, $sql) {
    $db = InstapageCmsPluginDBModel::getInstance();
    $errorMessage = $e->getMessage();

    if (strpos($sql, $db->debugTable) === false && $errorMessage !== '') {
      $messages = array(
        'Query: ' . $sql,
        'Error: ' . $errorMessage
     );

      InstapageCmsPluginHelper::writeDiagnostics(implode("\n", $messages), 'DB Error');
    }
  }

  /**
   * Gets the list of slugs used by Drupal 7 posts.
   *
   * @return array List of slugs used by posts.
   */
  private function getPostSlugs() {
    $editUrl = $this->getSiteURL();
    $dbPrefix = $this->getDBPrefix();
    $sql = 'SELECT pid AS id, SUBSTRING(alias, 2) AS slug, CONCAT(\'' . $editUrl . '\', source, \'/edit\') AS editUrl FROM ' . $dbPrefix . 'url_alias';
    $results = $this->getResults($sql);

    return $results;
  }

  /**
   * Prepares the remote request response to unify response object in all integrated CMSes.
   *
   * @param object $request Request result.
   *
   * @return array Standard Instapage plugin request response array.
   */
  private function prepareResponse($request) {
    return array(
      'body' => (string) isset($request->data) ? $request->data : '',
      'status' => (string) isset($request->status_message) ? $request->status_message : '',
      'code' => isset($request->code) ? $request->code : '0',
      'headers' => isset($request->headers) ? $request->headers : ''
   );
  }
}
