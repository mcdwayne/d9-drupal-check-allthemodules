<?php

/**
 * @file
 * Contains Drupal\google_adwords_path\GoogleAdwordsPathTracker.
 *
 * This service allows you to match GoogleAdwordsPathConfigs either
 * by route or by path, to match (retrieve) and register a number
 * of configs for tracking.
 *
 * The service registers for the KERNEL::Controller event and registers and
 * configs that match the current path, but the clean url and the router path.
 */

namespace Drupal\google_adwords_path;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Symfony\Component\Routing\Route;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\Event;
use Drupal\google_adwords_path\Entity\GoogleAdwordsPathConfig;
use Drupal\google_adwords\GoogleAdwordsTracker;

/**
 * Class GoogleAdwordsPathTracker.
 *
 * @package Drupal\google_adwords_path
 */
class GoogleAdwordsPathTracker implements EventSubscriberInterface, ContainerInjectionInterface {

  /**
   * @const string GOOGLE_ADWORDS_PATH_TREE_CACHE_CID
   *   cache cid used to cache the collected path tree
   */
  const GOOGLE_ADWORDS_PATH_TREE_CACHE_CID = 'GoogleAdwordsPathTracker_Tree';

  /**
   * @const string GOOGLE_ADWORDS_PATH_TREE_NODEKEY
   *   tree path object key
   */
  const GOOGLE_ADWORDS_PATH_TREE_NODEKEY = '#'.'pathConfigs';

  /**
   * @const string GOOGLE_ADWORDS_PATH_TREE_WILDCARD
   *   tree path key that is a wildcard
   */
  const GOOGLE_ADWORDS_PATH_TREE_WILDCARD = '*';

  /**
   * Drupal data cache, to save optimized path data.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache_data;

  /**
   * Entity type manager, used to retrieve config entity data for the paths
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entity_type_manager;

  /**
   * Current Route match for path matching the current route
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $current_route_match;

  /**
   * Drupal\google_adwords\GoogleAdwordsTracker definition.
   *
   * @var \Drupal\google_adwords\GoogleAdwordsTracker
   */
  protected $google_adwords_tracker;

  /**
   * GoogleAdwordsPathTracker constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_data
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   * @param \Drupal\google_adwords\GoogleAdwordsTracker $google_adwords_tracker
   */
  public function __construct(CacheBackendInterface $cache_data, EntityTypeManager $entity_type_manager, CurrentRouteMatch $current_route_match, GoogleAdwordsTracker $google_adwords_tracker) {
    $this->cache_data = $cache_data;
    $this->entity_type_manager = $entity_type_manager;
    $this->current_route_match = $current_route_match;
    $this->google_adwords_tracker = $google_adwords_tracker;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cache.data'),
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('google_adwords.tracker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::CONTROLLER => 'event_KernelController'

      // Originally I thought that this element would be best, but it doesn't fire
      //RoutingEvents::FINISHED => 'event_RoutingFinished',
    ];
  }

  /**
   * Event handler, register path tracking after the controller has finished
   *
   * @see EventsAPI
   *
   * @todo maybe find a better event to attach to
   */
  public function event_KernelController() {
    $this->registerCurrentRoute();
  }

  /**
   * Handler Routing finished event to register any path configs that match with
   * the adwords tracker
   *
   * @see eventAPI
   */
  public function registerCurrentRoute() {
    foreach ($this->matchCurrentRoute() as $pathConfig) {
      /**
       * @var \Drupal\google_adwords_path\Entity\GoogleAdwordsPathConfig $pathConfig
       */

      $this->registerPathConfig($pathConfig);
    }
  }

  /**
   * Add tracking using settings from a GoogleAdwordsPathConfig object
   *
   * @param \Drupal\google_adwords_path\Entity\GoogleAdwordsPathConfig $pathConfig
   * @return null
   */
  public function registerPathConfig(GoogleAdwordsPathConfig $pathConfig) {
    return $this->google_adwords_tracker->addTracking( // $conversion_id, $label = NULL, $value = NULL, $language = NULL, $color = NULL, $format = NULL
      $pathConfig->get('conversion_id'),
      $pathConfig->get('label'),
      $pathConfig->get('words'),
      $pathConfig->get('language'),
      $pathConfig->get('color'),
      $pathConfig->get('format')
    );
  }

  /**
   * Return an array of PathConfigs that match the current route
   *
   * @returns \Drupal\google_adwords_path\Entity\GoogleAdwordsPathConfig[]
   */
  public function matchCurrentRoute() {
    /**
     * @var string[] array of string paths
     */
    $paths = [
      Url::fromRoute('<current>')->getInternalPath(),
      $this->current_route_match->getRouteObject()->getPath()
    ];

    return $this->matchPaths($paths);
  }

  /**
   * Match Route object to an Adwords Conversion Path
   *
   * @param \Symfony\Component\Routing\Route $route to match to
   * @returns \Drupal\google_adwords_path\Entity\GoogleAdwordsPathConfig[]
   */
  public function matchRoute(Route $route) {
    /**
     * @var string[] array of paths to match from the route
     */
    $paths = [
      $route->getPath()
    ];

    return $this->matchPaths($paths);
  }

  /**
   * @param string[] $paths
   * @return \Drupal\google_adwords_path\Entity\GoogleAdwordsPathConfig[]
   */
  public function matchPaths(array $paths) {
    /**
     * @var \Drupal\google_adwords_path\Entity\GoogleAdwordsPathConfig[] $configs
     */
    $configs = [];

    foreach($paths as $path) {
      /**
       * @var string|array $path
       */
      $configs += $this->matchPath($path);
    }
    return $configs;
  }

  /**
   * Try to match a single path to PathCongifs
   *
   * @param string|array $path
   * @return \Drupal\google_adwords_path\Entity\GoogleAdwordsPathConfig[]
   */
  public function matchPath($path) {
    // convert string paths to arrays
    if (is_string($path)) {
      $path = explode('/', trim($path));
    }

    /**
     * @var array tree of PathConfigs
     *   a tree of path configs organized by path element
     */
    $tree = $this->buildPathTree();

    /**
     * @var array $matches
     *   an array of config_path ids that matched the path
     */
    $matches = self::_matchPath_recursive($tree, $path);
    /**
     * @var \Drupal\Core\Entity\EntityStorageInterface $path_storage
     */
    $path_storage = $this->entity_type_manager->getStorage('google_adwords_path_config');
    return $path_storage->loadMultiple($matches);
  }

  /**
   * Navigate down the path tree to try to match the path to a node in the tree
   *
   * @param array $tree
   * @param array $path
   * @return array
   *  configs that match the path
   */
  protected static function _matchPath_recursive(array $tree, array $path) {
    if (count($path) === 0) {
      if (isset($tree[static::GOOGLE_ADWORDS_PATH_TREE_NODEKEY])) {
        return $tree[static::GOOGLE_ADWORDS_PATH_TREE_NODEKEY];
      }
      return [];
    }
    else {
      /**
       * @var string $pathElement
       *   a single element of the path
       */
      $pathElement = '';
      while (empty($pathElement)) {
        if (count($path)==0) {
          break;
        }
        $pathElement = array_shift($path);
      }


      if (isset($tree[static::GOOGLE_ADWORDS_PATH_TREE_WILDCARD])) {
        // we found a wildcard path, so it takes everything
        return static::_matchPath_recursive($tree[static::GOOGLE_ADWORDS_PATH_TREE_WILDCARD], []);
      }
      else if (isset($tree[$pathElement])) {
        // we found a matching element
        return static::_matchPath_recursive($tree[$pathElement], $path);
      }
      else {
        // no match
        return [];
      }

    }
  }

  /**
   * Retrieve the path tree from cache, or ask for it to be rebuild
   * @param boolean $reset
   *   Invalidate the cache
   * @return array
   *   A path tree similar to a render tree, with an element per path argument
   */
  public function buildPathTree($reset=FALSE) {
    /**
     * @var array $tree
     *   path tree
     */
    $tree = [];

    /**
     * @var object|false $cache
     */
    $cache = false;

    if (!$reset) {
      $cache = $this->cache_data->get(self::GOOGLE_ADWORDS_PATH_TREE_CACHE_CID);
    }

    if ($cache=== FALSE) {
      $tree = $this->_buildPathTree();
      $this->cache_data->set(self::GOOGLE_ADWORDS_PATH_TREE_CACHE_CID, $tree, CacheBackendInterface::CACHE_PERMANENT);
    }
    else {
      $tree = $cache->data;
    }

    if (is_array($tree)) {
      return $tree;
    }
    else {
      return [];
    }
  }

  /**
   * Build the GoogleAdwordsPathConfig path render tree
   *
   * @return array $tree
   *   This is in the format of a render tree, with #configs=GoogleAdwordsPathConfig[]
   */
  private function _buildPathTree() {
    /**
     * @var \Drupal\Core\Entity\EntityStorageInterface $path_storage
     */
    $path_storage = $this->entity_type_manager->getStorage('google_adwords_path_config');

    /**
     * @var GoogleAdwordsPathConfig[] $pathConfigs
     */
    $pathConfigs = $path_storage->loadByProperties(array('enabled' => TRUE));

    $tree = [];

    foreach ($pathConfigs as $pathConfig) {
      /**
       * @todo we have to deal with strings|arrays in the config entity
       *
       * @var string[] $paths
       */
      $paths = $pathConfig->get('paths');
      if (is_string($paths)) { // make sure it's an array
        $paths = explode("\n", $paths);
      }

      foreach ($paths as $path) {
        if (is_string($path)) {
          $path = explode('/', trim($path));
        }
        static::_buildPathTreeRecursive($pathConfig, $tree, $path);
      }
    }

    return $tree;
  }

  /**
   * @param \Drupal\google_adwords_path\Entity\GoogleAdwordsPathConfig $pathConfig
   * @param array $tree
   *   a render tree of exploded path parts, into which the pathconfig item should be inserted
   * @param array $path
   *   an exploded partial route|path to match the tree
   */
  private static function _buildPathTreeRecursive(GoogleAdwordsPathConfig &$pathConfig, array &$tree, array $path) {
    if (count($path) == 0) {
      if (!isset($tree[static::GOOGLE_ADWORDS_PATH_TREE_NODEKEY])) {
        $tree[static::GOOGLE_ADWORDS_PATH_TREE_NODEKEY] = [];
      }
      $tree[static::GOOGLE_ADWORDS_PATH_TREE_NODEKEY][$pathConfig->id()] = $pathConfig->id();
    }
    else {
      /**
       * @var string $pathElement
       *   a single element of the path
       */
      $pathElement = '';

      /**
       * @note some url syntax can create empty exploded items:
       *   - leading /
       *   - double-slash
       */
      while (empty($pathElement)) {
        if (count($path)==0) {
          break;
        }
        $pathElement = array_shift($path);
      }

      if (empty($pathElement)) {
        // no more items in the path, pass it back for adding
        self::_buildPathTreeRecursive($pathConfig, $tree, []);
      }
      else {
        // sanity check on the tree array (prevent PHP warnings)
        if (!isset($tree[$pathElement])) {
          $tree[$pathElement] = [];
        }
        self::_buildPathTreeRecursive($pathConfig, $tree[$pathElement], $path);
      }
    }
  }
}
