<?php

namespace Drupal\micon;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\UseCacheBackendTrait;
use Drupal\Core\Cache\Cache;
use Drupal\Component\Assertion\Inspector;
use Drupal\micon\Entity\Micon;

/**
 * Class MiconIconManager.
 *
 * @package Drupal\micon
 */
class MiconIconManager {

  use UseCacheBackendTrait;

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityManager;

  /**
   * Cached icons array.
   *
   * @var array
   */
  protected $icons;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, CacheBackendInterface $cache_backend) {
    $this->entityManager = $entity_manager;
    $this->setCacheBackend($cache_backend, 'micon.icons', array('micon.icons'));
  }

  /**
   * Match an icon_id against the Micon package icon definitions.
   *
   * @param string $icon_id
   *   The icon id as specified within the IcoMoon selection.json file.
   *
   * @return \Drupal\micon\MiconIcon|null
   *   The found MiconIcon.
   */
  public function getIconMatch($icon_id) {
    $icons = $this->getFlattenedIcons();
    return isset($icons[$icon_id]) ? $icons[$icon_id] : NULL;
  }

  /**
   * Get all available icons.
   *
   * @return array
   *   Nested list of \Drupal\micon\Entity\Micon entities grouped by
   *   package id.
   */
  public function getIcons() {
    $icons = $this->getCachedDefinitions();
    if (!isset($icons)) {
      $icons = $this->loadIcons();
      $this->setCachedPackages($icons);
    }
    return $icons;
  }

  /**
   * Get a flat list of all icons.
   *
   * @return array
   *   Flat list of \Drupal\micon\Entity\Micon entities.
   */
  public function getFlattenedIcons() {
    $icons = [];
    foreach ($this->getIcons() as $package_icons) {
      $icons += $package_icons;
    }
    return $icons;
  }

  /**
   * Load all available micon icons.
   *
   * @return array
   *   List of \Drupal\micon\Entity\Micon entities to store in cache.
   */
  protected function loadIcons() {
    $definitions = [];
    foreach (Micon::loadActive() as $micon) {
      $definitions[$micon->id()] = $micon->getIcons();
    }
    return $definitions;
  }

  /**
   * Initialize the cache backend.
   *
   * Plugin icons are cached using the provided cache backend. The
   * interface language is added as a suffix to the cache key.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param string $cache_key
   *   Cache key prefix to use, the language code will be appended
   *   automatically.
   * @param array $cache_tags
   *   (optional) When providing a list of cache tags, the cached Micon
   *   icons are tagged with the provided cache tags. These cache tags can
   *   then be used to clear the corresponding cached Micon icons. Note
   *   that this should be used with care! For clearing all cached Micon
   *   icons of a Micon manager, call that Micon manager's
   *   clearCachedDefinitions() method. Only use cache tags when cached Micon
   *   icons should be cleared along with other, related cache entries.
   */
  public function setCacheBackend(CacheBackendInterface $cache_backend, $cache_key, array $cache_tags = array()) {
    assert(Inspector::assertAllStrings($cache_tags), 'Cache Tags must be strings.');
    $this->cacheBackend = $cache_backend;
    $this->cacheKey = $cache_key;
    $this->cacheTags = $cache_tags;
  }

  /**
   * Returns the cached Micon icons.
   *
   * @return array|null
   *   On success this will return an array of Micon icons. On failure
   *   this should return NULL, indicating to other methods that this has not
   *   yet been defined. Success with no values should return as an empty array
   *   and would actually be returned by the getIcons() method.
   */
  protected function getCachedDefinitions() {
    if (!isset($this->icons) && $cache = $this->cacheGet($this->cacheKey)) {
      $this->icons = $cache->data;
    }
    return $this->icons;
  }

  /**
   * Sets a cache of Micon icons.
   *
   * @param array $icons
   *   List of icons to store in cache.
   */
  protected function setCachedPackages(array $icons) {
    $this->cacheSet($this->cacheKey, $icons, Cache::PERMANENT, $this->cacheTags);
    $this->icons = $icons;
  }

}
