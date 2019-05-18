<?php

/**
 * Class responsible for managing the landing pages.
 */
class InstapageCmsPluginPageModel {

  /**
   * @var object Class instance.
   */
  private static $pageModel = null;

  /**
   * @var int Page statistics cache duration in minutes.
   */
  private static $statCacheDuration = 15;

  /**
   * Gets the class instance.
   *
   * @return object Class instance.
   */
  public static function getInstance() {
    if (self::$pageModel === null) {
      self::$pageModel = new InstapageCmsPluginPageModel();
    }

    return self::$pageModel;
  }

  /**
   * Updates the page baset on passed $data object.
   *
   * @param object $data Data object.
   *
   * @return integer|boolean Insert ID of false on error.
   */
  public function update($data) {
    $id = InstapageCmsPluginHelper::getVar($data->id, 0);
    $instapageId = InstapageCmsPluginHelper::getVar($data->landingPageId, null);
    $type = InstapageCmsPluginHelper::getVar($data->type);
    $slug = InstapageCmsPluginHelper::getVar($data->slug);
    $enterpriseUrl = InstapageCmsPluginConnector::getHomeURL();

    if ($slug) {
      $enterpriseUrl .= '/' . $slug;
    }

    $db = InstapageCmsPluginDBModel::getInstance();
    $sql = 'INSERT INTO ' . $db->pagesTable . ' VALUES(%s, %s, %s, %s, NOW(), \'\', NULL, %s) ON DUPLICATE KEY UPDATE instapage_id = %s, slug = %s, type = %s, time = NOW(), stats_cache = \'\', stats_cache_expires = NULL, enterprise_url = %s';

    if ($db->query($sql, $id, $instapageId, $slug, $type, $enterpriseUrl, $instapageId, $slug, $type, $enterpriseUrl)) {
      return ($id == 0) ? $db->lastInsertId() : $id;
    } else {
      return false;
    }
  }

  /**
   * Gets all the stored pages.
   *
   * @param array $fields Fields to retrieve. Default: array('*').
   * @param array $conditions. List of conditions. Logical operator between conditions: AND.
   *
   * @return array Lst of results.
   */
  public function getAll($fields = array('*'), $conditions = array()) {
    $db = InstapageCmsPluginDBModel::getInstance();
    $sql = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $db->pagesTable;

    if (count($conditions)) {
      $sql .= ' WHERE ' . implode(' AND ', $conditions);
    }

    return $db->getResults($sql);
  }

  /**
   * Gest the single page based on ID.
   *
   * @param int $id ID of the page.
   * @param array $fields List of fields to retrieve. Default: array('*').
   *
   * @return object Page object.
   */
  public function get($id, $fields = array('*')) {
    $db = InstapageCmsPluginDBModel::getInstance();
    $sql = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $db->pagesTable . ' WHERE id = \'' . $id . '\'';

    return $db->getRow($sql);
  }

  /**
   * Gest the single page based on slug.
   *
   * @param string $slug Slug of the page.
   * @param array $fields List of fields to retrieve. Default: array('*').
   *
   * @return object Page object.
   */
  public function getBySlug($slug, $fields = array('*')) {
    $db = InstapageCmsPluginDBModel::getInstance();
    $sql = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $db->pagesTable . ' WHERE slug = %s AND type=\'page\'';

    return $db->getRow($sql, $slug);
  }

  /**
   * Gest the single page based on type and slug.
   *
   * @param string $type Type of the page. ('page'|'home'|'404').
   * @param string $slug Slug of the page.
   * @param array $fields List of fields to retrieve. Default: array('*').
   *
   * @return object Page object.
   */
  public function getByType($type, $slug = '', $fields = array('*')) {
    $db = InstapageCmsPluginDBModel::getInstance();
    $sql = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $db->pagesTable . ' WHERE type = %s';

    if ($slug) {
      $sql = $sql . ' AND slug = %s';

      return $db->getRow($sql, $type, $slug);
    }
    else {
      return $db->getRow($sql, $type);
    }
  }

  /**
   * Gest the single page based on ID in Instapage app.
   *
   * @param int $instapageId ID in Instapage app.
   * @param array $fields List of fields to retrieve. Default: array('*').
   *
   * @return array List of page objects.
   */
  public function getByInstapageId($instapageId, $fields = array('*')) {
    $db = InstapageCmsPluginDBModel::getInstance();
    $sql = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $db->pagesTable . ' WHERE instapage_id = ' . $instapageId;

    return $db->getResults($sql);
  }

  /**
   * Gets the cached statistics for pages.
   *
   * @param array $ids List of page IDs.
   *
   * @return array List of objects with stats cache informations.
   */
  public function getPageStatsCache($ids) {
    if (!is_array($ids) || !count($ids)) {
      return null;
    }

    $db = InstapageCmsPluginDBModel::getInstance();

    foreach ($ids as &$item) {
      $item = intval($item);
    }

    $idsSet = implode(', ', $ids);
    $expireInSeconds = self::$statCacheDuration * 60;
    $sql = 'SELECT instapage_id, stats_cache FROM ' . $db->pagesTable . ' WHERE instapage_id IN(' . $idsSet . ') AND stats_cache_expires + ' . $expireInSeconds . ' > ' . time();
    $results = $db->getResults($sql);
    $stats = array();

    if ($results) {
      foreach ($results as &$item) {
        $stats[$item->instapage_id] = json_decode($item->stats_cache);
      }

      return $stats;
    }

    return array();
  }

  /**
   * Sends a request to publish a page in Instapage app.
   *
   * @param object $data Page object.
   *
   * @return string JSON object with API response.
   */
  public function publishPage($data) {
    $api = InstapageCmsPluginAPIModel::getInstance();
    $subaccount = InstapageCmsPluginSubaccountModel::getInstance();
    $url = $data->slug ? InstapageCmsPluginConnector::getHomeURL() . '/' . $data->slug : InstapageCmsPluginConnector::getHomeURL();
    $url = InstapageCmsPluginHelper::prepareUrlForUpdate($url);
    $tokens = InstapageCmsPluginHelper::getVar($data->apiTokens, false);
    $success = true;

    if (!$tokens) {
      $tokens = $subaccount->getAllTokens();
    }

    $oldPageId = InstapageCmsPluginHelper::getVar($data->id, null);
    $newInstapageId = InstapageCmsPluginHelper::getVar($data->landingPageId, null);

    if ($oldPageId) {
      $oldPage = $this->get($oldPageId, array('instapage_id'));

      if ($oldPage->instapage_id != $newInstapageId) {
        $apiData = array(
          'page' => $oldPage->instapage_id,
          'url' => '',
          'publish' => 0
       );
        $headers = array('accountkeys' => InstapageCmsPluginHelper::getAuthHeader($tokens));
        $responseJson = $api->apiCall('page/edit', $apiData, $headers);
        $response = json_decode($responseJson);

        if (!InstapageCmsPluginHelper::checkResponse($response, null, false) || !$response->success) {
          $success = false;
        }
      }
    }

    if ($success) {
      $apiData = array(
        'page' => $data->landingPageId,
        'url' => $url,
        'publish' => 1
     );
      $headers = array('accountkeys' => InstapageCmsPluginHelper::getAuthHeader($tokens));
      $responseJson = $api->apiCall('page/edit', $apiData, $headers);
      $response = json_decode($responseJson);
    }

    if (!$success || !InstapageCmsPluginHelper::checkResponse($response, null, false) || !$response->success) {
      if (isset($response->message) && $response->message !== '') {
        return InstapageCmsPluginHelper::formatJsonMessage(InstapageCmsPluginConnector::lang($response->message), 'ERROR');
      }
      else {
        return InstapageCmsPluginHelper::formatJsonMessage(InstapageCmsPluginConnector::lang('There was an error during page update process.'), 'ERROR');
      }

      return false;
    }

    $updatedId = $this->update($data);

    if ($updatedId) {
      return json_encode((object) array(
        'status' => 'OK',
        'message' => InstapageCmsPluginConnector::lang('Page updated successfully.'),
        'updatedId' => $updatedId
     ));
    }
    else {
      return InstapageCmsPluginHelper::formatJsonMessage(InstapageCmsPluginConnector::lang('There was a database error during page update process.'), 'ERROR');
    }
  }

  /**
   * Migrates the depracated pages to current DB structure.
   *
   * @param array $data List of pages to migrate.
   *
   * @return array List of messages to display as a migration raport.
   */
  public function migrateDeprecatedData($data) {
    InstapageCmsPluginHelper::writeDiagnostics($data, 'Migration data');
    $raport = array();

    if (!is_array($data) || !count($data)) {
      return $raport;
    }

    foreach ($data as $deprecatedPage) {
      if ($deprecatedPage->type == 'home') {
        $deprecatedPage->slug = '';
      }

      $landingPagesById = $this->getByInstapageId($deprecatedPage->landingPageId);
      $landingPagesBySlug = null;
      $landingPagesByType = null;

      if (count($landingPagesById)) {
        $newLandingPage = array_pop($landingPagesById);
        $raport[] = InstapageCmsPluginConnector::lang('Old version of page (slug: %s, Instapage ID: %s) is present in new database (slug: %s) and won\'t be migrated.', $deprecatedPage->slug, $deprecatedPage->landingPageId, $newLandingPage->slug);

        continue;
      }

      if ($deprecatedPage->slug && $deprecatedPage->type == 'page') {
        $landingPagesBySlug = $this->getBySlug($deprecatedPage->slug);
      }

      if ($landingPagesBySlug) {
        $newLandingPage = $landingPagesBySlug;
        $raport[] = InstapageCmsPluginConnector::lang('Slug: %s is already taken in new database. Old page (slug: %s, Instapage ID: %s) won\'t be migrated.', $deprecatedPage->slug, $deprecatedPage->slug, $deprecatedPage->landingPageId);

        continue;
      }

      if ($deprecatedPage->type !== 'page') {
        $landingPagesByType = $this->getByType($deprecatedPage->type);
      }

      if ($landingPagesByType) {
        $newLandingPage = $landingPagesByType;
        $raport[] = InstapageCmsPluginConnector::lang('One %s page already exists in new database. Old page (slug: %s, Instapage ID: %s) won\'t be migrated.', $deprecatedPage->type, $deprecatedPage->slug, $deprecatedPage->landingPageId);

        continue;
      }

      if ($this->update($deprecatedPage)) {
        $raport[] = InstapageCmsPluginConnector::lang('Old version of page (slug: %s, Instapage ID: %s) successfully migrated.', $deprecatedPage->slug, $deprecatedPage->landingPageId);
      }
      else {
        $raport[] = InstapageCmsPluginConnector::lang('Old version of page (slug: %s, Instapage ID: %s) cannot be migrated due to database error.', $deprecatedPage->slug, $deprecatedPage->landingPageId);
      }
    }

    InstapageCmsPluginHelper::writeDiagnostics($raport, 'Migration raport');

    return $raport;
  }

  /**
   * Saves the statistics of the page as a cache.
   *
   * @param array $data Lst of elements to save.
   */
  public function savePageStatsCache($data) {
    $db = InstapageCmsPluginDBModel::getInstance();

    foreach ($data as $key => $item) {
      $sql = 'UPDATE ' . $db->pagesTable . ' SET stats_cache = %s, stats_cache_expires = ' . time() . ' WHERE instapage_id = %s';
      $db->query($sql, json_encode($item), $key );
    }
  }

  /**
   * Gets a landing page saved as a homepage.
   *
   * @param array $fields List of fields to retrieve. Default: array('*').
   *
   * @return object Page object.
   */
  public function getHomepage($fields = array('*')) {
    $db = InstapageCmsPluginDBModel::getInstance();
    $sql = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $db->pagesTable . ' WHERE type=\'home\'';

    return $db->getRow($sql);
  }

  /**
   * Gets a landing page saved as a 404 page.
   *
   * @param array $fields List of fields to retrieve. Default: array('*').
   *
   * @return object Page object.
   */
  public function get404($fields = array('*')) {
    $db = InstapageCmsPluginDBModel::getInstance();
    $sql = 'SELECT ' . implode(', ', $fields) . ' FROM ' . $db->pagesTable . ' WHERE type=\'404\'';

    return $db->getRow($sql);
  }

  /**
   * Gets the data about landing pages stores in local database and completes them with data from Instapage app.
   *
   * @param array localData List of pages stored locally. Data will be changed during the process.
   *
   * @param array $appData List of information from the Instapage app.
   */
  public function mergeListPagesResults(&$localData, $appData) {
    foreach ($localData as &$localItem) {
      $instapageId = $localItem->instapage_id;
      $appItem = $this->getPageFromArray($instapageId, $appData);

      if (!is_null($appItem)) {
        $localItem->screenshot = $appItem->screenshot;
        $localItem->title = $appItem->title;
        $localItem->subaccount = $appItem->subaccount;
      }
    }
  }

  /**
   * Checks (and returns) if a landing page should be displayed instead of normal content served by CMS.
   *
   * @param string $type Type of page to check ('page', 'home' or '404').
   * @param string $slug Slug to check. Default: ''.
   *
   * @return string HTML to display.
   */
  public function check($type, $slug = '') {
    if (!InstapageCmsPluginConnector::isHtmlReplaceNecessary()) {
      return;
    }

    $result = $this->getByType($type, $slug, array('instapage_id', 'slug', 'enterprise_url'));

    if (!$result) {
      return;
    }

    $result->slug = $result->slug ? $result->slug : $slug;
    $result->enterprise_url = $result->enterprise_url ? $result->enterprise_url : InstapageCmsPluginConnector::getHomeURL() . '/' . $result->slug;
    $result->enterprise_url = rtrim($result->enterprise_url, '/');

    return $result;
  }

  /**
   * Displays the landing page.
   *
   * @param object $page Landing page to display.
   * @param int $forcedStatus Status to be set as a header. Default: null.
   */
  public function display($page, $forcedStatus = null) {
    $instapageId = $page->instapage_id;
    $slug = $page->slug;
    $host = parse_url($page->enterprise_url, PHP_URL_HOST);
    InstapageCmsPluginHelper::writeDiagnostics($slug . ' : ' . $instapageId, 'slug : instapage_id');

    $api = InstapageCmsPluginAPIModel::getInstance();
    $querySufix = '';
    $cookies = $_COOKIE;

    if (isset($_GET) && !empty($_GET)) {
      $querySufix = '?' . http_build_query($_GET);
    }
    elseif (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING'])) {
      $querySufix = '?' . $_SERVER['QUERY_STRING'];
    }

    if (is_array($cookies) && count($cookies)) {
      $cookiesWeNeed = array("instapage-variant-{$instapageId}");

      foreach ($cookies as $key => $value) {
        if (!in_array($key, $cookiesWeNeed)) {
          unset($cookies[$key]);
        }
      }
    }

    $url = preg_replace('/https?:\/\/' . $host . '/', INSTAPAGE_ENTERPRISE_ENDPOINT, $page->enterprise_url);
    $url .= $querySufix;
    $result = $api->enterpriseCall($url, $host, $cookies);
    $cookieString = @InstapageCmsPluginHelper::getVar($result['headers']['set-cookie'], '');
    $instapageVariant = InstapageCmsPluginHelper::getVariant($cookieString);

    if (!empty($instapageVariant)) {
      setcookie(
        "instapage-variant-{$instapageId}",
        $instapageVariant,
        strtotime('+12 month')
     );
    }

    if ($forcedStatus) {
      $status = $forcedStatus;
    }
    else {
      $status = InstapageCmsPluginHelper::getVar($result['code'], 200);
    }

    $html = InstapageCmsPluginHelper::getVar($result['body']);
    $html = $this->disableCloudFlareScriptReplace($html);
    $html = $this->fixHtmlHead($html);

    if ($html) {
      ob_start();
      InstapageCmsPluginHelper::disableCaching();
      InstapageCmsPluginHelper::httpResponseCode($status);
      print $html;
      ob_end_flush();
      die();
    }
    else {
      return false;
    }
  }

  /**
   * Deletes a page from local DB.
   *
   * @param int $id ID of a page to be deleted.
   */
  public function delete($id) {

    $db = InstapageCmsPluginDBModel::getInstance();
    $sql = 'DELETE FROM ' . $db->pagesTable . ' WHERE id = %s';

    return $db->query($sql, $id);
  }

  /**
   * Gets the page object from an array of page objects.
   *
   * @param int $id ID of a page.
   * @param array $array List of page objects.
   *
   * @return object|null Page object or null if no pages found.
   */
  private function getPageFromArray($id, $array) {
    if (is_array($array)) {
      foreach ($array as $item) {
        if ($item->id == $id) {
          return $item;
        }
      }
    }

    return null;
  }

  /**
   * Composes a random slug.
   *
   * @param bool $prefix Do you want to add a prefix?
   *
   * @return string Random slug.
   */
  public function getRandomSlug($prefix = true) {
    $randomPrefix = 'random-url-';
    $randomSufixSet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomSufixLength = 10;
    $randomString = '';

    for ($i = 0; $i < $randomSufixLength; $i++) {
      $randomString .= $randomSufixSet[rand(0, strlen($randomSufixSet) - 1)];
    }

    return $prefix ? $randomPrefix . $randomString : $randomString;
  }

  /**
   * Removes the CloudFlare JS from a landing page content.
   *
   * @param string $html HTML of a landing page.
   *
   * @return string HTML without CloudFlare script.
   */
  private function disableCloudFlareScriptReplace($html) {
    $pattern = '/(<script)(type="text\/javascript")?(.*?)>/';

    return preg_replace($pattern, "$1$2 data-cfasync=\"false\" $3>", $html);
  }

  /**
   * Sets up the proper URL for Instapage proxy, if it is enabled.
   *
   * @param string $html HTML to be fixed.
   *
   * @return string HTML with propely set proxy URLs.
   */
  public function fixHtmlHead($html) {
    $useProxy = InstapageCmsPluginHelper::getOption('crossOrigin', false);

    if ($useProxy) {
      $html = str_replace('PROXY_SERVICES', str_replace(array('http://', 'https://'), array('//', '//'), InstapageCmsPluginConnector::getHomeURL()) ."/instapage-proxy-services?url=", $html);
    }

    $searchArray = array(
      '<meta name="iy453p9485yheisruhs5" content="" />',
      '<meta name="robots" content="noindex, nofollow" />'
   );

    if (strpos($html, $searchArray[0]) !== false) {
      $html = str_replace($searchArray, '', $html);
    }

    return $html;
  }
}
