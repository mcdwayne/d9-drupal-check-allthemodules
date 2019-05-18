<?php

namespace Drupal\google_api_client\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\google_api_client\Service\GoogleApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Google Client Callback Controller.
 *
 * @package Drupal\google_api_client\Controller
 */
class Callback extends ControllerBase {

  /**
   * Google API Client.
   *
   * @var \Drupal\google_api_client\Service\GoogleApiClient
   */
  private $googleApiClient;

  /**
   * Callback constructor.
   *
   * @param \Drupal\google_api_client\Service\GoogleApiClient $googleApiClient
   *   Google API Client.
   */
  public function __construct(GoogleApiClient $googleApiClient) {
    $this->googleApiClient = $googleApiClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('google_api_client.client')
    );
  }

  /**
   * Callback URL for Google API Auth.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return array
   *   Return markup for the page.
   */
  public function callbackUrl(Request $request) {
    $code = $request->get('code');
    $token = $this->googleApiClient->getAccessTokenByAuthCode($code);

    // If token valid.
    if (isset($token['access_token'])) {
      // TODO fix the deprecated drupal_set_message.
      drupal_set_message($this->t('Access tokens saved'));
    }
    else {
      // TODO fix the deprecated drupal_set_message.
      drupal_set_message($this->t('Failed to get access token. Check log messages.'), 'error');
    }

    return new RedirectResponse(Url::fromRoute('google_api_client.settings')->toString());
  }

}
