<?php

namespace Drupal\site_map\Controller;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for update routes.
 */
class SitemapController implements ContainerInjectionInterface {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs update status data.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module Handler Service.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * Controller for /sitemap.
   *
   * @return array
   *   Renderable string.
   */
  public function buildPage() {
    $site_map = array(
      '#theme' => 'site_map',
    );

    $config = \Drupal::config('site_map.settings');
    if ($config->get('css') != 1) {
      $site_map['#attached']['library'] = array(
        'site_map/site_map.theme',
      );
    }

    return $site_map;
  }

  /**
   * Returns site map page's title.
   *
   * @return string
   *   Site map page title.
   */
  public function getTitle() {
    $config = \Drupal::config('site_map.settings');
    return $config->get('page_title');
  }

}
