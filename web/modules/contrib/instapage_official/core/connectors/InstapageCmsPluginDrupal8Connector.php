<?php

use Drupal\Core\Database\Database as Database;

/**
 * Class that utilizes native Drupal 8 functions to perform actions like remote requests and DB operations.
 */
class InstapageCmsPluginDrupal8Connector {

  /**
   * @var string $name Name of the CMS.
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

    try {
      $statement = $this->prepare($sql);

      if (count($args)) {
        return $statement->execute($args);
      } else {
        return $statement->execute();
      }

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
   * Prepares the basic query with proper metadata/tags and base fields.
   *
   * @param string $sql SQL query. %s can be used to output pre-formatted values.
   *
   * @return string SQL query ready to execute in Drupal 8.
   */
  public function prepare($sql) {
    $sql = str_replace(array('\'%s\'', '%s'), '?', $sql);
    $connection = Database::getConnection();

    return $connection->prepare($sql);
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

    try {
      $statement = $this->prepare($sql);

      if (count($args)) {
        $statement->execute($args);
      } else {
        $statement->execute();
      }

      return $statement->fetch(PDO::FETCH_OBJ);
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

    try {
      $statement = $this->prepare($sql);

      if (count($args)) {
        $statement->execute($args);
      } else {
        $statement->execute();
      }

      return $statement->fetchAll(PDO::FETCH_OBJ);
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
    $connectionKey = Database::getConnection()->getKey();
    $settings = Database::getConnectionInfo($connectionKey);

    if (!isset($settings['prefix']) && is_array($settings)) {
      $settings = array_pop($settings);
    }

    if (isset($settings['prefix']) && is_array($settings['prefix'])) {
      $settings['prefix'] = array_pop($settings['prefix']);
    }

    return isset($settings['prefix']) ? $settings['prefix'] : '';
  }

  /**
   * Gets charset collation.
   *
   * @return string Database charset collation.
   */
  public function getCharsetCollate() {
    return 'COLLATE utf8mb4_general_ci';
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
    try {
      if ($method == 'POST' && (!is_array($data) || !count($data))) {
        $data = array('ping' => true);
        InstapageCmsPluginHelper::writeDiagnostics($data, 'Request (' . $method . ') data empty. Ping added.');
      }

      $formParams = $data;

      if ($method == 'GET' && is_array($data)) {
        $dataString = http_build_query($data, '', '&');
        $url .= '?' . urldecode($dataString);
        $formParams = array();
      }

      $cookies = @InstapageCmsPluginHelper::getVar($data['cookies'], array());
      $cookieJar = false;

      if (!empty($cookies)) {
        $domain = parse_url($url, PHP_URL_HOST);
        $cookieJar = \GuzzleHttp\Cookie\CookieJar::fromArray($cookies, $domain);
      }

      $client = \Drupal::httpClient();
      $request = $client->request(
        $method,
        $url,
        array(
          'allow_redirects' => array(
            'max' => 5
         ),
          'connect_timeout' => 45,
          'synchronous' => true,
          'cookies' => $cookieJar,
          'version' => '1.0',
          'form_params' => $formParams,
          'headers' => $headers
       )
     );

      if ($request->getStatusCode() === 200) {
        return $this->prepareResponse($request);
      } else {
        return array('status' => 'ERROR', 'message' => InstapageCmsPluginInstapageCmsPluginConnector::lang('Request failed with status %s.', $request->getStatusCode()));
      }
    } catch (Exception $e) {
      return array('status' => 'ERROR', 'message' => InstapageCmsPluginConnector::lang('Request failed.'));
    }
  }

  /**
   * Performs remote POST request.
   *
   * @uses InstapageCmsPluginDrupal8Connector::remoteRequest().
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
   * @uses InstapageCmsPluginDrupal8Connector::remoteRequest().
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
   * Prepares the remote request response to unify response object in all integrated CMSes.
   *
   * @param object $request Request result.
   *
   * @return array Standard Instapage plugin request response array.
   */
  private function prepareResponse($request) {
    $headers = $request->getHeaders();
    $headers['set-cookie'] = @InstapageCmsPluginHelper::getVar($headers['Set-Cookie'][0], '');

    return array(
      'body' => (string) $request->getBody(),
      'status' => $request->getReasonPhrase(),
      'code' => $request->getStatusCode(),
      'headers' => $headers
   );
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
      if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
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

    if (strpos($requestUrl, '/user') === 0 || (isset($_GET['q']) && $_GET['q'] == 'user')) {
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

    if (strpos($requestUrl, '/admin') === 0 || (isset($_GET['q']) && $_GET['q'] == 'admin')) {
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

    $page->display($result);
  }

  /**
   * Checks (and displays) if a landing page marked as homepage should be displayed instead of normal CMS homepage.
   *
   * @uses InstapageCmsPluginDrupal8Connector::checkPage()
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
   * @uses InstapageCmsPluginDrupal8Connector::checkPage()
   */
  public function check404() {
    if (is_404()) {
      $this->checkPage('404');

      return true;
    }

    return false;
  }

  /**
   * Checks (and displays) if a landing page hould be displayed instead of normal CMS page under current URL.
   *
   * @uses InstapageCmsPluginDrupal8Connector::checkPage()
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
   * Geta a list of slugs that can't be used to publish a landing page.
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
    $sitename = \Drupal::config('system.site')->get('name');

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
    $mailManager = \Drupal::service('plugin.manager.mail');
    $module = 'instapage_cms_plugin';
    $key = 'custom_email';
    $params['message'] = $message;
    $params['subject'] = $subject;
    $langcode = \Drupal::currentUser()->getPreferredLangcode();
    $send = true;

    return $mailManager->mail($module, $key, $to, $langcode, $params, NULL, $send);
  }

  /**
   * Gets the landing pages saved in legacy DB structure.
   *
   * @return array List of landing pages from legacy DB structure.
   */
  public function getDeprecatedData() {
    $config = \Drupal::config('instapage.pages');
    $pages = $config->get('instapage_pages');
    $results = array();

    foreach ($pages as $key => $slug) {
      $pageObj = new stdClass;
      $pageObj->id = 0;
      $pageObj->landingPageId = $key;
      $pageObj->slug = $slug;
      $pageObj->type = 'page';
      $pageObj->enterprise_url = $pageObj->slug ? InstapageCmsPluginConnector::getHomeURL() . '/' . $pageObj->slug : InstapageCmsPluginConnector::getHomeURL();
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
    return \Drupal\Component\Utility\Html::escape($html);
  }

  /**
   * Checks if there is a need to replace content of CMS with a landing page. Prevents content replacement on admin/login pages.
   *
   * @return bool True if replace is possible.
   */
  public function isHtmlReplaceNecessary() {
    if ($this->isAdmin() || $this->isLoginPage() || InstapageCmsPluginHelper::isCustomParamPresent()) {
      InstapageCmsPluginHelper::writeDiagnostics('isAdmin || isLoginPage || isCustomParamPresent', 'HTML Replace is not necessary');

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
}
