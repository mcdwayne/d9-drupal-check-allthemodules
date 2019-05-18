<?php

/**
 * Class responsible for managing the subaccounts in Instapage app.
 */
class InstapageCmsPluginSubaccountModel {

  /**
   * @var object Class instance.
   */
  private static $subaccountModel = null;

  /**
   * @var array List of subaccount tokens.
   */
  private $subaccountTokens = null;

  /**
   * Gets the class instance.
   *
   * @return object Class instance.
   */
  public static function getInstance() {
    if (self::$subaccountModel === null) {
      self::$subaccountModel = new InstapageCmsPluginSubaccountModel();
    }

    return self::$subaccountModel;
  }

  /**
   * Gets all the tokens - stored in plugin's settings and bound to currently used app user account.
   *
   * @return array List of tokens.
   */
  public function getAllTokens() {
    if ($this->subaccountTokens === null) {
      $tokens = InstapageCmsPluginHelper::getTokens();
      $accountKeys = $this->getAccountBoundTokens();
      $this->subaccountTokens = array_merge($tokens, $accountKeys);
    }

    return $this->subaccountTokens;
  }

  /**
   * Gets the list of tokens bound to subaccount. User has to be logged in via email and password.
   *
   * @return array List of tokens bound to an account.
   */
  public function getAccountBoundTokens() {
    $api = InstapageCmsPluginAPIModel::getInstance();
    $userToken = InstapageCmsPluginHelper::getOption('plugin_hash');
    $accountKeys = array();

    if ($userToken) {
      $headers = array('usertoken' => $userToken);
      $responseJson = $api->apiCall('page/get-account-keys', null, $headers);
      $response = json_decode($responseJson);

      if (!is_null($response) && $response->success) {
        $accountKeys = $response->data->accountkeys;
      }
    }

    return $accountKeys;
  }

  /**
   * Gets the list of subaccounts of currently logged in user.
   *
   * @param string $format Format for the response. Default: 'json'.
   *
   * @return (string|array) List of subaccounts as a JSON string or an array.
   */
  public function getAccountBoundSubAccounts($format = 'json') {
    $api = InstapageCmsPluginAPIModel::getInstance();
    $tokens = $this->getAccountBoundTokens();
    $subAccounts = array();

    if (is_array($tokens) && count($tokens)) {
      $headers = array('accountkeys' => InstapageCmsPluginHelper::getAuthHeader($tokens));
      $response = json_decode($api->apiCall('page/get-sub-accounts-list', null, $headers));
      $subAccounts = @InstapageCmsPluginHelper::getVar($response->data, null);
    }

    if ($format == 'json') {
      echo json_encode((object) array(
        'status' => 'OK',
        'data' => $subAccounts
     ));
    } else {
      return $subAccounts;
    }
  }

  /**
   * Sets the status of subaccount in Instapage app. Subaccount can be connected to or disconnected from a CMS.
   *
   * @param string $status Status to be set. Default: 'connect'. 'disconnect' is another option.
   * @param array $tokens List of tokens, that are meant to be connected of disconnected.
   * @param bool $silent Do you want a message to appear?
   */
  public function setSubAccountsStatus($status = 'connect', $tokens = null, $silent = false) {
    $api = InstapageCmsPluginAPIModel::getInstance();
    $subaccount = InstapageCmsPluginSubaccountModel::getInstance();
    $post = InstapageCmsPluginHelper::getPostData();

    if ($tokens !== null) {
      $selectedSubaccounts = $tokens;
    } else {
      $selectedSubaccounts = InstapageCmsPluginHelper::getVar($post->data->tokens, array());
    }

    if (count($selectedSubaccounts)) {
      $tokens = $subaccount->getAllTokens();
      $headers = array('accountkeys' => InstapageCmsPluginHelper::getAuthHeader($tokens));
      $data = array(
        'accountkeys' => base64_encode(json_encode($selectedSubaccounts)),
        'status' => $status,
        'domain' => InstapageCmsPluginConnector::getHomeURL(false)
     );

      $response = json_decode($api->apiCall('page/connection-status', $data, $headers));

      if ($silent) {
        return;
      }

      if (
        !InstapageCmsPluginHelper::checkResponse($response, null, false) ||
        !$response->success ||
        !isset($response->data->changed) ||
        $response->data->changed != count($selectedSubaccounts)
     ) {
        $action = array();
        $action[0] = $status == 'connect' ? 'connected to' : 'disconnected from';
        $action[1] = $status == 'connect' ? 'connect' : 'disconnect';

        if (count($selectedSubaccounts) > 1) {
          $message = InstapageCmsPluginHelper::getVar($response->message, InstapageCmsPluginConnector::lang('There was an error, selected subaccounts are not properly %s app. Try to %s subaccounts again.', $action[0], $action[1]));
        } else {
          $message = InstapageCmsPluginHelper::getVar($response->message, InstapageCmsPluginConnector::lang('There was an error, selected subaccount is not properly %s app. Try to %s subaccounts again.', $action[0], $action[1]));
        }

        echo InstapageCmsPluginHelper::formatJsonMessage($message, 'ERROR');
      } else {
        $action = array();
        $action[0] = $status == 'connect' ? 'Selected subaccounts' : 'Subaccounts bound to your account';
        $action[1] = $status == 'connect' ? 'connected' : 'disconnected';

        if (count($selectedSubaccounts) > 1) {
          $message = InstapageCmsPluginHelper::getVar($response->message, InstapageCmsPluginConnector::lang('%s are %s.', $action[0], $action[1]));
        } else {
          $message = InstapageCmsPluginHelper::getVar($response->message, InstapageCmsPluginConnector::lang('Selected subaccount is %s.', $action[1]));
        }

        echo InstapageCmsPluginHelper::formatJsonMessage($message);
      }
    } else {
      echo InstapageCmsPluginHelper::formatJsonMessage(InstapageCmsPluginConnector::lang('No subaccounts were connected.'));
    }
  }

  /**
   * Disconnects all subaccount bound to an account. User has to be looged in via email and password.
   *
   * @param bool $silent Do you want a message to appear? Default: false.
   */
  public function disconnectAccountBoundSubaccounts($silent = false) {
    $subAccounts = $this->getAccountBoundSubAccounts('array');

    if (count($subAccounts)) {
      $tokens = array();

      foreach ($subAccounts as $item) {
        $tokens[] = InstapageCmsPluginHelper::getVar($item->accountkey, '');
      }

      $this->setSubAccountsStatus('disconnect', $tokens, $silent);
    } else {
      if (!$silent) {
        echo InstapageCmsPluginHelper::formatJsonMessage(InstapageCmsPluginConnector::lang('Subaccounts bound to your account are dissconnected'));
      }
    }
  }
}
