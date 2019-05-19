<?php

namespace Drupal\sitemeta;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;

/**
 * Class SitemetaGenerator.
 */
class SitemetaGenerator {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a new SitemetaGenerator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AliasManagerInterface $alias_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasManager = $alias_manager;
  }

  /**
   * Returns sitemeta data for current page.
   *
   * @param string $path
   *   Internal path.
   * @param string $langcode
   *   Language of the page.
   *
   * @return object|false
   *   Returns Sitemeta entity if exists else false.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getSiteMeta($path, $langcode) {
    $sitemeta = $this->entityTypeManager->getStorage('sitemeta')->loadByProperties(['path' => $path, 'langcode' => $langcode]);
    // There will always be one entry if there is multiple
    // something went terribly wrong.
    if ($sitemeta) {
      return end($sitemeta);
    }
    else {
      $wildcardCheck = $this->wildcardCheck($path, $langcode);
      return $wildcardCheck;
    }
  }

  /**
   * Check if path matches any wildcard sitemeta and return it.
   *
   * If no matching results is found it will return FALSE.
   *
   * @param string $path
   *   Current page path.
   * @param string $langcode
   *   Current page language.
   *
   * @return object|bool
   *   Returns sitemeta if match is found or FALSE.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function wildcardCheck($path, $langcode) {
    $wildcards = $this->getAllWildcards();
    foreach ($wildcards as $wildcard) {
      $wildcardPath = strtok($wildcard->getPath(), '%');
      $alias = $this->aliasManager->getAliasByPath($path);
      if (strpos($path, $wildcardPath) !== FALSE) {
        return $wildcard;
      }
      // Current path service always returns internal path,
      // so we need to check the alias too.
      if (strpos($alias, $wildcardPath) !== FALSE) {
        return $wildcard;
      }

      return FALSE;
    }
  }

  /**
   * Get all sitemeta entries that have a wildcard selector.
   *
   * @return array
   *   Returns an array of sitemeta entities.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getAllWildcards() {
    $allSitemeta = $this->entityTypeManager->getStorage('sitemeta')->loadMultiple();
    $wildcards = [];
    foreach ($allSitemeta as $sitemeta) {
      if (strpos($sitemeta->getPath(), '%') !== FALSE) {
        $wildcards[] = $sitemeta;
      }
    }
    return $wildcards;
  }

}
