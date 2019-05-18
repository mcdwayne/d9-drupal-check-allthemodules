<?php

/**
 * @file
 * Droogle connector.
 */

namespace Drupal\droogle;

use Masterminds\HTML5\Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Link;
use Drupal\Core\Url;

class DroogleConnector {

  protected $config;

  public function __construct() {
    $this->config = \Drupal::getContainer()->get('config.factory')->getEditable('droogle.settings');
  }

  /**
   * Connect to Google Drive in online mode
   * to let user work with GDrive in Drupal through the web interface.
   */
  public function droogleGdriveConnect($redirect_url = NULL) {
    $client = new \Google_Client();
    $client->setClientId($this->config->get('client_id'));
    $client->setClientSecret($this->config->get('client_secret'));
    $client->setRedirectUri($this->getRedirectUri());
    // Required for background work. Needs more permissions. todo make it optional?
    $client->setApprovalPrompt('force');
    $client->setAccessType('offline');
    $client->addScope("https://www.googleapis.com/auth/drive");
    $service = new \Google_Service_Drive($client);

    // Disconnect from google drive.
    if (isset($_REQUEST['logout'])) {
      unset($_SESSION['upload_token']);
      $this->setRefreshToken(FALSE);
      $logout_redirect_url = !empty($_REQUEST['destination']) ? $_REQUEST['destination'] : $this->getRedirectUri();
      return new RedirectResponse($logout_redirect_url);
    }

    // If we have code from google.
    if (isset($_GET['code'])) {
      $client->authenticate($_GET['code']);
      $_SESSION['upload_token'] = $client->getAccessToken();
      $this->setRefreshToken($client->getRefreshToken());
      // Redirect to  custom redirect_url if it was sent.
      drupal_set_message(t('You have successfully authenticated into Google Drive'));
      if (!empty($redirect_url)) {
        return new RedirectResponse(filter_var($redirect_url, FILTER_SANITIZE_URL));
      }
    }

    // Set access token from session if we have it.
    if (isset($_SESSION['upload_token']) && $_SESSION['upload_token']) {
      $client->setAccessToken($_SESSION['upload_token']);
      if ($client->isAccessTokenExpired()) {
        unset($_SESSION['upload_token']);
      }
    }
    // We should be authorized.
    else {
      $return_result['auth_link'] = $this->getAuthLink($client->createAuthUrl());
    }
    $return_result['disconnect_link'] = $this->getDisconnectLink();
    $return_result['#client'] = $client;
    $return_result['#service'] = $service;

    return $return_result;
  }

  /**
   * Connect to Google Drive in offline mode
   * to let Drupal work with Google Drive in background mode.
   */
  public function droogleGdriveConnectOffline() {
    $client = new \Google_Client();
    $client->setClientId($this->config->get('client_id'));
    $client->setClientSecret($this->config->get('client_secret'));
    $client->addScope("https://www.googleapis.com/auth/drive");

    // We have no session, let's refresh the token.
    if ($refresh_token = $client->getRefreshToken()) {
      $client->refreshToken($refresh_token);
    }
    return new \Google_Service_Drive($client);
  }

  /**
   * Returns data from google drive to render it on your webpage.
   * @param null $redirect_url
   *   Url which will be used as destination for redirect after the successful auth in Google Drive.
   */
  public function droogleOpenGDrive($redirect_url = NULL) {
    // Connect to the google drive.
    $result = $this->droogleGdriveConnect($redirect_url);
    if (!is_array($result)) {
      return $result;
    }

    $client = $result['#client'];
    $service = $result['#service'];

    $refresh_token = $this->getRefreshToken();

    // If we have refresh token and access token is expired then try to refresh the token.
    if (!empty($refresh_token) && $client->isAccessTokenExpired()) {
      try {
        $client->refreshToken($refresh_token);
      } catch (\Google_Auth_Exception $e) {
        // Something went wrong. We have to auth again.
        $content = [
          'auth_link' => $result['auth_link'],
        ];

        return $content;
      }
    }
    // Show results.
    if ($client->getAccessToken()) {
      $content = array(
        '#theme' => 'droogle_list_files',
        '#service' => $service,
        '#disconnect_link' => $result['disconnect_link'],
        '#attached' => [
          'library' => [
            'droogle/droogle.main'
          ]
        ],
      );
    }
    // Show url to auth.
    elseif (!empty($result['auth_link'])) {
      $content = [
        'auth_link' => $result['auth_link'],
      ];
    }
    return $content;
  }

  /**
   * Save refresh code to use it later.
   */
  protected function setRefreshToken($token) {
    $account = \Drupal::currentUser();
    if ($account->id() == 0) {
      throw new Exception('User cannot be anonymous');
    }

    \Drupal::database()->upsert('droogle_users')
      ->fields([
        'uid' => $account->id(),
        'refresh_token' => $token
      ])
      ->key('uid')
      ->execute();
  }

  /**
   * Get refresh code.
   */
  public function getRefreshToken() {
    $account = \Drupal::currentUser();
    if ($account->id() == 0) {
      throw new Exception('User cannot be anonymous');
    }

    $query = \Drupal::database()->select('droogle_users', 'du');
    $query->addField('du', 'refresh_token');
    $query->condition('uid', $account->id());
    $refresh_token = $query->execute()->fetchField();

    return $refresh_token;
  }

  /**
   * Returns link to connect GDrive.
   */
  private function getAuthLink($authUrl) {
    $link = Link::fromTextAndUrl('Connect Me to Google Drive!', Url::fromUri($authUrl))->toRenderable();
    $link['#attributes']['class'] = ['connect-gdrive'];
    return $link;
  }

  /**
   * Returns link to disconnect the GDrive.
   */
  private function getDisconnectLink() {
    $query = [
      'logout' => TRUE,
      'destination' => \Drupal::service('path.current')->getPath()
    ];

    $url = Url::fromRoute('droogle', [], ['query' => $query]);
    $link = Link::fromTextAndUrl('Disconnect me from Google Drive!', $url)->toRenderable();;
    $link['#attributes']['class'] = ['disconnect-gdrive'];
    return $link;
  }

  /**
   * Returns redirect uri for google drive client.
   */
  private function getRedirectUri() {
    // Redirect URI is always static to catch the token.
    $droogle_redirect_uri = $this->config->get('droogle_redirect_callback');
    $droogle_redirect_uri = empty($droogle_redirect_uri) ? DROOGLE_BROWSER_URL : $droogle_redirect_uri;
    return 'http://' . $_SERVER['HTTP_HOST'] . '/' . $droogle_redirect_uri;
  }
}
