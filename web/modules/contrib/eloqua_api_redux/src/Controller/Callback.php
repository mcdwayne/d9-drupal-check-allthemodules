<?php

namespace Drupal\eloqua_api_redux\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\eloqua_api_redux\Service\EloquaApiClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class Eloqua API Callback Controller.
 *
 * @package Drupal\eloqua_api_redux\Controller
 */
class Callback extends ControllerBase {

  /**
   * Eloqua API Client.
   *
   * @var \Drupal\eloqua_api_redux\Service\EloquaApiClient
   */
  private $eloquaClient;

  /**
   * Callback constructor.
   *
   * @param \Drupal\eloqua_api_redux\Service\EloquaApiClient $eloquaClient
   *   Eloqua API Client.
   */
  public function __construct(EloquaApiClient $eloquaClient) {
    $this->eloquaClient = $eloquaClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('eloqua_api_redux.client')
    );
  }

  /**
   * Callback URL for Eloqua API Auth.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Request.
   *
   * @return array
   *   Return markup for the page.
   */
  public function callbackUrl(Request $request) {
    $code = $request->get('code');

    // Try to get the token.
    $token = $this->eloquaClient->getAccessTokenByAuthCode($code);

    // If token is not false.
    if ($token != FALSE) {
      // TODO fix the deprecated drupal_set_message.
      drupal_set_message('Access tokens saved');
    }
    else {
      // TODO fix the deprecated drupal_set_message.
      drupal_set_message('Failed to get access token. Check log messages.', 'error');
    }

    return new RedirectResponse(Url::fromRoute('eloqua_api_redux.settings')->toString());
  }

}
