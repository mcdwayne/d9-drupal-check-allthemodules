<?php

namespace Drupal\blackbaud_sky_api\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Drupal\blackbaud_sky_api\BlackbaudInterface;

/**
 * Defines a route subscriber to register a url for serving image styles.
 */
class BlackbaudRoutes implements ContainerInjectionInterface, BlackbaudInterface {

  /**
   * The Drupal state storage service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new BlackbaudRoutes object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('state')
    );
  }

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = [];
    // Grab the Config form option or the constant for the path.
    $path = $this->state->get('blackbaud_sky_api_redirect_uri', BlackbaudInterface::BLACKBAUD_SKY_API_REDIRECT_URI);

    $routes['blackbaud_sky_api.oauth_redirect_uri'] = new Route(
      '/' . $path,
      [
        '_controller' => '\Drupal\blackbaud_sky_api\Controller\DefaultController::redirectUriCallback',
      ],
      [
        '_access' => 'TRUE',
      ]
    );
    return $routes;
  }

}
