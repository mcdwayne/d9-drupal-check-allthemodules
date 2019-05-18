<?php

namespace Drupal\deactivate_account\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines a route subscriber to register a url for serving image styles.
 */
class DeactivateAccountRoutes implements ContainerInjectionInterface {

  /**
   * The stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Constructs a new PathProcessorImageStyles object.
   *
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager service.
   */
  public function __construct(StreamWrapperManagerInterface $stream_wrapper_manager) {
    $this->streamWrapperManager = $stream_wrapper_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('stream_wrapper_manager')
    );
  }

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = array();
    // Generate image derivatives of publicly available files. If clean URLs are
    // disabled image derivatives will always be served through the menu system.
    // If clean URLs are enabled and the image derivative already exists, PHP
    // will be bypassed.
    if (\Drupal::config('deactivate_account.settings')->get('deactivate_account_tab')) {

      $routes['deactivate_account.form'] = new Route(
        '/user/{user}/deactivate',
        array(
          '_form' => 'Drupal\deactivate_account\Form\DeactivateAccountForm',
          '_title' => 'Deactivate',
        ),
        array(
          '_custom_access' => '\Drupal\deactivate_account\Access\AccessCheck::access',
        )
      );
    }
    else {
      $deactivate_account_path = \Drupal::config('deactivate_account.settings')->get('deactivate_account_path_container')['deactivate_account_path'];
      if($deactivate_account_path) {
        $routes['deactivate_account.form'] = new Route(
          '/user/' . $deactivate_account_path,
          array(
            '_form' => 'Drupal\deactivate_account\Form\DeactivateAccountForm',
            '_title' => 'Deactivate',
          ),
          array(
            '_custom_access' => '\Drupal\deactivate_account\Access\AccessCheck::access',
          )
        );
      }
    }
    return $routes;
  }
}
