<?php

namespace Drupal\blackbaud_sky_api;

use Drupal\blackbaud_sky_api\Blackbaud;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Class BlackbaudOauth.
 *
 * @package Drupal\blackbaud_sky_api
 */
class BlackbaudOauth extends Blackbaud {

  /**
   * Gets the Code token via user interaction on a redirect.
   */
  public function getCode() {
    // Auth Url.
    $this->setUrl(parent::getOauthBaseUrl() . '/authorization');

    // Set the auth query params.
    $query = [
      'query' => [
        'client_id' => $this->state->get('blackbaud_sky_api_application_id'),
        'response_type' => 'code',
        'redirect_uri' => $this->redirectUri,
      ],
    ];

    // The Url used for auth.
    $url = Url::fromUri(parent::getUrl(), $query)->toString();

    // Go to the url and return to the redirect URI.
    $response = new RedirectResponse($url);
    $response->send();
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthCode($type, $code) {
    parent::getAuthCode($type, $code);
  }

}
