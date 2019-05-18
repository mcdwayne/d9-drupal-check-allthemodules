<?php

namespace Drupal\google_crawl_errors\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\google_crawl_errors\GoogleCrawlErrors;
use Google_Client;
use Google_Service_Webmasters;

/**
 * Controller routines for Google crawl errors routes.
 */
class GoogleCrawlErrorsController extends ControllerBase {

  /**
   * Output OAuth page.
   */
  public function oauth() {
    $config = \Drupal::config('google_crawl_errors.settings');
    $oauth_secret = json_decode($config->get('oauth_secret_json'), TRUE);

    $client = new Google_Client();
    $client->setAuthConfig($oauth_secret);
    $client->setAccessType('offline');
    $client->setApprovalPrompt('force');
    $client->setRedirectUri(\Drupal::request()->getSchemeAndHttpHost() . '/google-crawl-errors/oauth');
    $client->addScope(Google_Service_Webmasters::WEBMASTERS_READONLY);

    if (!isset($_GET['code'])) {
      $auth_url = $client->createAuthUrl();
      header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
      die();
    }
    else {
      $client->authenticate($_GET['code']);
      die(json_encode($client->getAccessToken()));
    }

  }

  /**
   * Output report page.
   */
  public function list() {
    $config = \Drupal::config('google_crawl_errors.settings');
    $category = 'notFound';
    $platform = 'web';

    $gce = new GoogleCrawlErrors();
    $data = $gce->getResultData($config->get('site_id'), $category, $platform);

    $contents = $gce->prepareOutput($data, 200);

    return [
      '#title' => t('Google crawl errors'),
      '#theme' => 'google_crawl_errors_block',
      '#contents' => $contents,
      '#show_full_link' => FALSE,
      '#cache' => [
        'max-age' => 0,
      ],
      '#attached' => [
        'library' => ['google_crawl_errors/report-block'],
      ],
    ];
  }

}
