<?php

/**
 * @file
 * Contains \Drupal\offline_app\Controller\HomescreenController.
 */

namespace Drupal\offline_app\Controller;

use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\views\Views;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class HomescreenController.
 *
 * @package Drupal\offline_app\Controller
 */
class HomescreenController extends ControllerBase {

  /**
   * The config factory
   *
   * @var \Drupal\core\Config\ConfigFactoryInterface.
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Returns the homescreen manifest.
   */
  public function homescreenManifest() {
    $build = [];
    $build['name'] = $this->config('system.site')->get('name');
    $build['display'] = 'standalone';
    $build['start_url'] = 'offline/appcache-fallback';

    if ($icon_path = $this->config('offline_app.homescreen')->get('icon_192')) {
      $icon = new \stdClass();
      $icon->src = $icon_path;
      $icon->sizes = '192x192';
      $icon->type = 'image/' . $this->config('offline_app.homescreen')->get('icon_192_type');
      $icon->density = '4.0';
      $build['icons'] = [0 => $icon];
    }

    $response = new CacheableResponse(json_encode($build), Response::HTTP_OK, ['content-type' => 'application/json']);
    $cache = $response->getCacheableMetadata();
    $cache->addCacheTags(['homescreen']);
    return $response;
  }

}
