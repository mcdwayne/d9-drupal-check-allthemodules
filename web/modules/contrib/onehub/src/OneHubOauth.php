<?php

namespace Drupal\onehub;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\onehub\OneHub;

/**
 * Class OneHub.
 *
 * @package Drupal\onehub
 */
class OneHubOauth extends OneHub {

  /**
   * Gets the Authorization Code token via user interaction on a redirect.
   */
  public function getAuthCode() {
    // Auth Url.
    $this->setUrl($this->baseUrl . '/oauth/authorize');

    // Set the auth query params.
    $query = [
      'query' => [
        'client_id' => \Drupal::config('onehub.settings')->get('onehub_application_id'),
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
  public function getAccessCode($code) {
    parent::getAccessCode($code);
  }

}
