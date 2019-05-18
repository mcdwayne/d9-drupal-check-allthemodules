<?php

/**
 * Main controller for AJAX actions. Results are returned as encoded JSON objects. Data for actions are stored in $_POST['data'] table.
 */
class InstapageCmsPluginAjaxController {

  /**
   * @var object Controller object for Singleton implementation.
   */
  private static $ajaxController = null;

  /**
   * Gets an instance of the object.
   */
  public static function getInstance() {

    if (self::$ajaxController === null) {
      self::$ajaxController = new InstapageCmsPluginAjaxController();
    }

    return self::$ajaxController;
  }

  /**
   * Executes an action set in the request.
   *
   * @param string $action Action to execute.
   * @param mixed $data Data passed to action.
   */
  public function doAction($action, $data = null) {
    InstapageCmsPluginHelper::writeDiagnostics($action, 'AJAX Action');

    if (!InstapageCmsPluginConnector::currentUserCanManage()) {
      echo InstapageCmsPluginHelper::formatJsonMessage(InstapageCmsPluginConnector::lang('You don\'t have permission to perform that action.'), 'ERROR');
      exit;
    }

    switch ($action) {
      case 'loginUser':
        $this->loginUser();
      break;

      case 'getApiTokens':
        $this->getApiTokens();
      break;

      case 'connectSubAccounts':
        $subaccount = InstapageCmsPluginSubaccountModel::getInstance();
        $subaccount->setSubAccountsStatus('connect');
      break;

      case 'disconnectSubAccounts':
        $subaccount = InstapageCmsPluginSubaccountModel::getInstance();
        $subaccount->setSubAccountsStatus('disconnect');
      break;

      case 'disconnectAccountBoundSubaccounts':
        $subaccount = InstapageCmsPluginSubaccountModel::getInstance();
        $subaccount->disconnectAccountBoundSubaccounts();
      break;

      case 'getAccountBoundSubAccounts':
        $subaccount = InstapageCmsPluginSubaccountModel::getInstance();
        $subaccount->getAccountBoundSubAccounts();
      break;

      case 'updateOptions':
        if (InstapageCmsPluginHelper::updateOptions($data) !== false) {
          echo InstapageCmsPluginHelper::formatJsonMessage(InstapageCmsPluginConnector::lang('Configuration updated'), 'OK');
        } else {
          echo InstapageCmsPluginHelper::formatJsonMessage(InstapageCmsPluginConnector::lang('There was an error during configuration save'), 'ERROR');
        }
      break;

      case 'getOptions':
        echo json_encode((object) array(
          'status' => 'OK',
          'data' => InstapageCmsPluginHelper::getOptions()
       ));
      break;

      case 'getLog':
        $this->getLog();
      break;

      case 'clearLog':
        $log = InstapageCmsPluginDebugLogModel::getInstance();
        $log->clear();
        echo InstapageCmsPluginHelper::formatJsonMessage(InstapageCmsPluginConnector::lang('Log cleared'), 'OK');
      break;

      case 'getMasterToken':
        $this->getMasterToken();
      break;

      case 'loadListPages':
        $this->loadListPages();
      break;

      case 'loadEditPage':
        $this->loadEditPage();
      break;

      case 'getLandingPages':
        $this->getLandingPages();
      break;

      case 'getStats':
        $this->getStats();
      break;

      case 'publishPage':
        $this->publishPage();
      break;

      case 'deletePage':
        $this->deletePage();
      break;

      case 'loadSettings':
        echo json_encode((object) array(
          'status' => 'OK',
          'html' => InstapageCmsPluginHelper::loadTemplate('settings', false),
          'initialData' => InstapageCmsPluginHelper::getOptions()
       ));
      break;

      case 'getProhibitedSlugs':
        $data = InstapageCmsPluginConnector::getSelectedConnector()->getProhibitedSlugs();
        echo json_encode((object) array(
          'status' => 'OK',
          'data' => $data
       ));
      break;

      case 'validateToken':
        $this->validateToken();
      break;

      case 'migrateDeprecatedData':
        $data = InstapageCmsPluginConnector::getSelectedConnector()->getDeprecatedData();
        $page = InstapageCmsPluginPageModel::getInstance();
        $raport = $page->migrateDeprecatedData($data);
        $raportStr = implode('<br />', $raport);
        echo InstapageCmsPluginHelper::formatJsonMessage($raportStr);
      break;

      default:
        echo InstapageCmsPluginHelper::formatJsonMessage(InstapageCmsPluginConnector::lang('Unsupported InstapageCmsPluginAjaxController action'), 'ERROR');
    }
  }

  /**
   * Performs login by email and password in Instapage APP.
   */
  private function loginUser() {
    $api = InstapageCmsPluginAPIModel::getInstance();
    $post = InstapageCmsPluginHelper::getPostData();
    $email = urldecode(InstapageCmsPluginHelper::getVar($post->data->email, ''));
    $password = urldecode(InstapageCmsPluginHelper::getVar($post->data->password, ''));
    $response = json_decode($api->authorise($email, $password));

    if (!InstapageCmsPluginHelper::checkResponse($response, null, false) || !$response->success) {
      $message = InstapageCmsPluginHelper::getVar($response->message, '');
      echo InstapageCmsPluginHelper::formatJsonMessage($message, 'ERROR');

      return false;
    } else {
      echo json_encode((object) array(
        'status' => 'OK',
        'data' => (object) $response->data
     ));
    }
  }

  /**
   * Validates tokens stored in the DB.
   */
  private function validateToken() {
    $api = InstapageCmsPluginAPIModel::getInstance();
    $post = InstapageCmsPluginHelper::getPostData();
    $token = InstapageCmsPluginHelper::getVar($post->data->token, null);
    $headers = array('accountkeys' => InstapageCmsPluginHelper::getAuthHeader(array($token)));
    $response = json_decode($api->apiCall('page/get-sub-accounts-list', null, $headers));
    $subAccount = @InstapageCmsPluginHelper::getVar($response->data, null);

    if (!InstapageCmsPluginHelper::checkResponse($response, null, false) || !$response->success || count($subAccount) == 0) {
      echo json_encode((object) array(
        'status' => 'OK',
        'valid' => false
     ));
    } else {
      echo json_encode((object) array(
        'status' => 'OK',
        'valid' => true
     ));
    }
  }

  /**
   * Gets the debug log stored in the DB.
   */
  private function getLog() {
    $log = InstapageCmsPluginDebugLogModel::getInstance();
    $sitenameSanitized = InstapageCmsPluginConnector::getSitename(true);

    try {
      $data = $log->getLogHTML();
      echo json_encode((object) array(
        'status' => 'OK',
        'data' => $data,
        'sitename' => $sitenameSanitized
     ));
    } catch (Exception $e) {
      echo InstapageCmsPluginHelper::formatJsonMessage($e->getMessage(), 'ERROR');
    }
  }

  /**
   * Gets the API  tokens stored in the DB.
   */
  private function getApiTokens() {
    $subaccount = InstapageCmsPluginSubaccountModel::getInstance();
    $tokens = $subaccount->getAllTokens();
    echo json_encode((object) array(
      'status' => 'OK',
      'data' => $tokens
   ));
  }


  /**
   * Loads edit page.
   */
  private function loadEditPage() {
    $api = InstapageCmsPluginAPIModel::getInstance();
    $subaccount = InstapageCmsPluginSubaccountModel::getInstance();
    $post = InstapageCmsPluginHelper::getPostData();
    InstapageCmsPluginHelper::writeDiagnostics($post, 'Edit page POST');
    $tokens = InstapageCmsPluginHelper::getVar($post->apiTokens, false);

    if (!$tokens) {
      $tokens = $subaccount->getAllTokens();
    }

    $pageData = null;
    $subAccounts = null;
    $data = array();

    if (isset($post->data->id)) {
      $pageData = $post->data;
      $data['pages'] = array($post->data->instapage_id);
    }

    $headers = array('accountkeys' => InstapageCmsPluginHelper::getAuthHeader($tokens));
    $response = json_decode($api->apiCall('page/get-sub-accounts-list', $data, $headers));

    if (InstapageCmsPluginHelper::checkResponse($response)) {
      $subAccounts = $response->data;
    } else {
      return false;
    }

    $initialData = array('subAccounts' => $subAccounts, 'page' => $pageData);
    InstapageCmsPluginHelper::writeDiagnostics($initialData, 'Edit page initialData');

    echo json_encode((object) array(
      'status' => 'OK',
      'html' => InstapageCmsPluginHelper::loadTemplate('edit', false),
      'data' => (object) $initialData
   ));
  }

  /**
   * Loads listing page.
   */
  private function loadListPages() {
    $requestLimit = 300;
    $post = InstapageCmsPluginHelper::getPostData();
    $page = InstapageCmsPluginPageModel::getInstance();
    InstapageCmsPluginHelper::writeDiagnostics($post, 'List page POST');
    $api = InstapageCmsPluginAPIModel::getInstance();
    $subaccount = InstapageCmsPluginSubaccountModel::getInstance();
    $localPagesArray = $page->getAll(array('id', 'instapage_id', 'slug', 'type', 'stats_cache', 'enterprise_url'));

    // WP Legacy code - automatic migration.
    $automaticMigration = InstapageCmsPluginHelper::getMetadata('automatic_migration', false);

    if (empty($automaticMigration) && !count($localPagesArray) && InstapageCmsPluginConnector::isWP() && InstapageCmsPluginConnector::getSelectedConnector()->legacyArePagesPresent()) {
      $data = InstapageCmsPluginConnector::getSelectedConnector()->getDeprecatedData();
      $page = InstapageCmsPluginPageModel::getInstance();
      $page->migrateDeprecatedData($data);
      $localPagesArray = $page->getAll(array('id', 'instapage_id', 'slug', 'type', 'stats_cache', 'enterprise_url'));
      InstapageCmsPluginHelper::updateMetadata('automatic_migration', time());
    }

    $pages = array();

    foreach ($localPagesArray as &$pageObject) {
      $pageObject->stats_cache = json_decode($pageObject->stats_cache);
      $pages[] = $pageObject->instapage_id;
    }

    $tokens = InstapageCmsPluginHelper::getVar($post->apiTokens, false);

    if (!$tokens) {
      $tokens = $subaccount->getAllTokens();
    }

    if (!count($tokens)) {
      echo json_encode((object) array(
        'status' => 'OK',
        'html' => InstapageCmsPluginHelper::loadTemplate('listing', false),
        'initialData' => $localPagesArray
     ));

      return true;
    }


    $headers = array('accountkeys' => InstapageCmsPluginHelper::getAuthHeader($tokens));
    $responses = array();

    for ($i = 0; $i * $requestLimit < count($pages); ++$i) {
      $dataSlice = array_slice($pages, $i * $requestLimit, $requestLimit);
      $data = array('pages' => $dataSlice);
      $responseJson = $api->apiCall('page/list', $data, $headers, 'GET');
      $response = json_decode($responseJson);

      if (InstapageCmsPluginHelper::checkResponse($response) && isset($response->data) && is_array($response->data)) {
        $responses[] = $response->data;
      } else {
        $responses[] = array();
      }
    }

    $mergedResponse = array();

    foreach ($responses as $r) {
      $mergedResponse = array_merge($mergedResponse, $r);
    }

    $page->mergeListPagesResults($localPagesArray, $mergedResponse);
    InstapageCmsPluginHelper::writeDiagnostics($localPagesArray, 'List page array');
    echo json_encode((object) array(
      'status' => 'OK',
      'html' => InstapageCmsPluginHelper::loadTemplate('listing', false),
      'initialData' => $localPagesArray
   ));
  }

  /**
   * Gets the landing pages stored in the DB.
   */
  private function getLandingPages() {
    $api = InstapageCmsPluginAPIModel::getInstance();
    $post = InstapageCmsPluginHelper::getPostData();
    $tokens = array($post->data->subAccountToken);
    $headers = array('accountkeys' => InstapageCmsPluginHelper::getAuthHeader($tokens));
    $responseJson = $api->apiCall('page/list', null, $headers);
    $response = json_decode($responseJson);
    $page = InstapageCmsPluginPageModel::getInstance();
    $publishedPages = $page->getAll(array('instapage_id'));
    $selfInstapageId  = @InstapageCmsPluginHelper::getVar($post->data->selfInstapageId, null);

    if (InstapageCmsPluginHelper::checkResponse($response)) {
      if (is_array($response->data)) {
        foreach ($response->data as $key => $returnedPage) {
          foreach ($publishedPages as $published_page) {
            if ($returnedPage->id != $selfInstapageId  && $returnedPage->id == $published_page->instapage_id) {
              unset($response->data[$key]);
              break;
            }
          }
        }

        $response->data = array_values($response->data);
      } else {
        $response->data = array();
      }

      echo json_encode((object) array(
        'status' => 'OK',
        'data' => $response->data
     ));
    } else {
      return false;
    }
  }

  /**
   * Gets the stats of landing pages from local cache or from app, if cache is not present / invalid.
   */
  private function getStats() {
    $post = InstapageCmsPluginHelper::getPostData();
    $page = InstapageCmsPluginPageModel::getInstance();
    $api = InstapageCmsPluginAPIModel::getInstance();
    $subaccount = InstapageCmsPluginSubaccountModel::getInstance();
    $pages = InstapageCmsPluginHelper::getVar($post->data->pages, array());

    if (!count($pages)) {
      InstapageCmsPluginHelper::writeDiagnostics('Stats cond', 'No pages in request');
      echo json_encode((object) array(
        'status' => 'OK',
        'data' => array()
     ));

      return true;
    }

    $cachedStats = $page->getPageStatsCache($pages);
    InstapageCmsPluginHelper::writeDiagnostics($cachedStats, 'Cached stats');
    $pagesWithoutStats = array();

    foreach ($pages as $instapageId) {
      if (!isset($cachedStats[$instapageId])) {
        $pagesWithoutStats[] = $instapageId;
      }
    }

    if (empty($pagesWithoutStats)) {
      echo json_encode((object) array(
        'status' => 'OK',
        'data' => $cachedStats
     ));

      return true;
    }

    $tokens = InstapageCmsPluginHelper::getVar($post->apiTokens, false);

    if (!$tokens) {
      $tokens = $subaccount->getAllTokens();
    }

    $headers = array('accountkeys' => InstapageCmsPluginHelper::getAuthHeader($tokens));
    $data = array('pages' => $pagesWithoutStats);
    $responseJson = $api->apiCall('page/stats', $data, $headers);
    $response = json_decode($responseJson);

    if (InstapageCmsPluginHelper::checkResponse($response)) {
      $stats = (array) InstapageCmsPluginHelper::getVar($response->data, array());
      $page->savePageStatsCache($stats);

      if (count($stats)) {
        $stats = array_merge($cachedStats, $stats);
      } else {
        $stats = $cachedStats;
      }

      echo json_encode((object) array(
        'status' => 'OK',
        'data' => $stats
     ));
    } else {
      return false;
    }
  }

  /**
   * Gathers data for 'publish' request.
   */
  private function publishPage() {
    $page = InstapageCmsPluginPageModel::getInstance();
    $post = InstapageCmsPluginHelper::getPostData();
    $data = $post->data;

    echo $page->publishPage($data);
  }

  /**
   * Deletes a page from DB.
   */
  private function deletePage() {
    $page = InstapageCmsPluginPageModel::getInstance();
    $api = InstapageCmsPluginAPIModel::getInstance();
    $subaccount = InstapageCmsPluginSubaccountModel::getInstance();
    $post = InstapageCmsPluginHelper::getPostData();
    $result = $page->get($post->data->id, array('instapage_id'));
    $instapageId = $result->instapage_id;
    $tokens = InstapageCmsPluginHelper::getVar($post->apiTokens, false);

    if (!$tokens) {
      $tokens = $subaccount->getAllTokens();
    }

    $data = array(
      'page' => $instapageId,
      'url' => '',
      'publish' => 0
    );
    $headers = array('accountkeys' => InstapageCmsPluginHelper::getAuthHeader($tokens));
    $response = json_decode($api->apiCall('page/edit', $data, $headers));

    $message = '';

    if (!InstapageCmsPluginHelper::checkResponse($response, null, false) || !$response->success) {
      $message .= InstapageCmsPluginConnector::lang('Page that you are removing (Instapage ID: %s) doesn\'t exist in your Instapage application\'s dashboard. It could have been deleted from app or created by another user. Deleting this page won\'t affect Instapage application\'s dashboard.', $instapageId);

      if (isset($response->message) && $response->message !== '') {
        $message .= InstapageCmsPluginConnector::lang(' Instapage app response: ' . $response->message);
      }
    }

    if (isset($post->data->id) && $page->delete($post->data->id)) {
      if ($message) {
        echo InstapageCmsPluginHelper::formatJsonMessage($message);
      } else {
        echo InstapageCmsPluginHelper::formatJsonMessage(InstapageCmsPluginConnector::lang('Page deleted successfully.'));
      }

      return true;
    } else {
      echo InstapageCmsPluginHelper::formatJsonMessage(InstapageCmsPluginConnector::lang('There was a database error during page delete process.'), 'ERROR');

      return false;
    }
  }
}
