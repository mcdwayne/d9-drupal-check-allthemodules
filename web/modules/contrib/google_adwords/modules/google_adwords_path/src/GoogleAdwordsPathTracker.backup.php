<?php

/**
 * @file
 * Contains Drupal\google_adwords_path\GoogleAdwordsPathTracker.
 */

namespace Drupal\google_adwords_path;

use Symfony\Component\Routing\Route;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\google_adwords_path\Entity\GoogleAdwordsPathConfig;
use Drupal\google_adwords\GoogleAdwordsTracker;

/**
 * Class GoogleAdwordsPathTracker.
 *
 * @package Drupal\google_adwords_path
 */
class GoogleAdwordsPathTrackerBackup implements EventSubscriberInterface {

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
   * Drupal\google_adwords\GoogleAdwordsTracker definition.
   *
   * @var \Drupal\google_adwords\GoogleAdwordsTracker
   */
  protected $google_adwords_tracker;

  /**
   * Drupal\Core\Routing\CurrentRouteMatch definition.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $current_route_match;

  /**
   * Drupal\Core\Cache\CacheBackendInterface definition.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache_data;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entity_type_manager;

  /**
   * GoogleAdwordsPathTracker constructor.
   * @param \Drupal\google_adwords\GoogleAdwordsTracker $google_adwords_tracker
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_data
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   */
  public function __construct(GoogleAdwordsTracker $google_adwords_tracker, CurrentRouteMatch $current_route_match, CacheBackendInterface $cache_data, EntityTypeManager $entity_type_manager) {
    $this->google_adwords_tracker = $google_adwords_tracker;
    $this->current_route_match = $current_route_match;
    $this->cache_data = $cache_data;
    $this->entity_type_manager = $entity_type_manager;
  }

  /**
   * @InheritDoc
   *
   * @event RoutingEvents::FINISHED
   *   Register any adwords paths related to the final route
   */
  public static function getSubscribedEvents() {
drupal_set_message(__method__ . ':: EVENT REGISTER');
    return [
      RoutingEvents::FINISHED => 'registerCurrentRoute'
    ];
  }

  /**
   * Handler Routing finished event to register any path configs that match with
   * the adwords tracker
   *
   * @see eventAPI
   */
  public function registerCurrentRoute() {
    drupal_set_message(__method__ . ':: EVENT TRIGGER');
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
     * @var Route $route
     */
    $route = $this->current_route_match->getRouteObject();
    return $this->matchRoute($route);
  }

  /**
   * Match Route object to an Adwords Conversion Path
   *
   * @param \Symfony\Component\Routing\Route $route to match to
   * @returns \Drupal\google_adwords_path\Entity\GoogleAdwordsPathConfig[]
   */
  public function matchRoute(Route $route) {
    return $this->matchPath($route->getPath());
  }

  /**
   * @param $path
   * @return array
   */
  protected function matchPath($path) {
    $tree = $this->buildPathTree();
    return self::_matchPath_recursive($tree, explode('/', $path));
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
      $pathElement = array_pop($path);

      if (isset($tree[$pathElement])) {
        return static::_matchPath_recursive($tree[$pathElement], $path);
      }
      else {
        return [];
      }
    }
  }

  /**
   * Retrieve the path tree from cache, or ask for it to be rebuild
   *
   * @return array
   *   A path tree similar to a render tree, with an element per path argument
   */
  protected function buildPathTree() {
    /**
     * @var array|false $tree
     */
    $tree = $this->cache_data->get(self::GOOGLE_ADWORDS_PATH_TREE_CACHE_CID);

    if ($tree === FALSE) {
      $tree = $this->_buildPathTree();
      $this->cache_data->set(self::GOOGLE_ADWORDS_PATH_TREE_CACHE_CID, $tree, CacheBackendInterface::CACHE_PERMANENT);
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
   *   This is in the format of a render tree, with #configs=[ GoogleAdwordsPathConfig ]
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
      if (is_string($paths)) {
        $paths = explode("\n", $paths);
      }

      foreach ($paths as $path) {
        static::_buildPathTreeRecursive($pathConfig, $tree, explode('/', $path));
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
  private static function _buildPathTreeRecursive(GoogleAdwordsPathConfig $pathConfig, array &$tree, array $path) {
    if (count($path) == 0) {
      if (!isset($tree[static::GOOGLE_ADWORDS_PATH_TREE_NODEKEY])) {
        $tree[static::GOOGLE_ADWORDS_PATH_TREE_NODEKEY] = [];
      }
      $tree[static::GOOGLE_ADWORDS_PATH_TREE_NODEKEY][] = $pathConfig;
    }
    else {
      /**
       * @var string $pathElement
       *   a single element of the path
       */
      $pathElement = array_pop($path);

      if (!isset($tree[$pathElement])) {
        $tree[$pathElement] = [];
      }

      self::_buildPathTreeRecursive($pathConfig, $tree[$pathElement], $path);
    }
  }

}
