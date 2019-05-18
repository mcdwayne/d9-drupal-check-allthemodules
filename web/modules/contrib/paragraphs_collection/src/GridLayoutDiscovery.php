<?php

namespace Drupal\paragraphs_collection;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Discovery\YamlDiscovery;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides common helper methods for style discovery.
 *
 * @todo Add documentation in the api file.
 */
class GridLayoutDiscovery implements GridLayoutDiscoveryInterface {
  use StringTranslationTrait;

  /**
   * Collection of styles with its definition.
   *
   * @var array
   */
  protected $gridLayoutsCollection = [];

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The controller resolver.
   *
   * @var \Drupal\Core\Controller\ControllerResolverInterface
   */
  protected $controllerResolver;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a new YamlStyleDiscovery.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend, ThemeHandlerInterface $theme_handler) {
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->cache = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries($layout) {
    $collection = $this->getGridLayouts();
    return isset($collection[$layout]['libraries']) ? $collection[$layout]['libraries'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getGridLayouts() {
    $cid = 'paragraphs_collection_grid_layouts';

    if ($this->gridLayoutsCollection) {
      return $this->gridLayoutsCollection;
    }
    else if ($cached = $this->cache->get($cid)) {
      $this->gridLayoutsCollection = $cached->data;
    }
    else {
      $yaml_discovery = $this->getYamlDiscovery();
      $this->gridLayoutsCollection = [];
      foreach ($yaml_discovery->findAll() as $provider => $layouts) {
        foreach ($layouts as $layout => $definition) {
          if (empty($definition['title'])) {
            throw new InvalidGridLayoutException('The "title" of "' . $layout . '" must be non-empty.');
          }
          $definition['title'] = $this->t($definition['title']);
          if (!empty($definition['description'])) {
            $definition['description'] = $this->t($definition['description']);
          }
          $definition['provider'] = $provider;
          $this->gridLayoutsCollection[$layout] = $definition;
        }
      }

      $this->cache->set($cid, $this->gridLayoutsCollection);
    }

    return $this->gridLayoutsCollection;
  }

  /**
   * {@inheritdoc}
   */
  public function getLayoutOptions() {
    $layout_options = [];
    $layouts = $this->getGridLayouts();
    foreach ($layouts as $name => $layout) {
      $layout_options[$name] = $layout['title'];
    }
    uasort($layout_options, 'strcasecmp');
    return $layout_options;
  }

  /**
   * {@inheritdoc}
   */
  public function getLayout($layout) {
    $layouts = $this->getGridLayouts();

    if (isset($layouts[$layout])) {
      return $layouts[$layout];
    }

    return [];
  }

  /**
   * Gets the intitiated YAML discovery.
   *
   * @return \Drupal\Core\Discovery\YamlDiscovery
   *   The YAML discovery object.
   */
  protected function getYamlDiscovery() {
    return new YamlDiscovery('paragraphs.grid_layouts', $this->moduleHandler->getModuleDirectories() + $this->themeHandler->getThemeDirectories());
  }

}
