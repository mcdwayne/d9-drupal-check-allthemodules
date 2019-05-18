<?php
/**
 * @file
 * Contains \Drupal\embridge_cache\Routing\EmbridgeCacheRoutes.
 */

namespace Drupal\embridge_cache\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a route subscriber to register a url for serving cached assets.
 */
class EmbridgeCacheRoutes implements ContainerInjectionInterface {

  /**
   * The stream wrapper manager service.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * Constructs a new EmbridgeCacheRoutes object.
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
   * @see \Drupal\image\Routing\ImageStyleRoutes::routes
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = array();
    $directory_path = $this->streamWrapperManager->getViaScheme('public')->getDirectoryPath();

    $routes['embridge_cache.cache_public'] = new Route(
      '/' . $directory_path . '/embridge_cache/{embridge_catalog}/{conversion}/{scheme}',
      [
        '_controller' => 'Drupal\embridge_cache\Controller\EmbridgeCacheDownloadController::deliver',
      ],
      [
        '_access' => 'TRUE',
      ]
    );
    return $routes;
  }

}
