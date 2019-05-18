<?php

namespace Drupal\set_front_page;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\node\NodeInterface;

/**
 * The set_front_page manager.
 */
class SetFrontPageManager {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The set front page manager.
   *
   * @var \Drupal\set_front_page\SetFrontPageManager
   */
  protected $entityTypeManager;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * The State object.
   *
   * @var \DrupalState
   */
  protected $state;

  /**
   * Construct the SetFrontPageManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tag invalidator service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entityTypeManager, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->config = $config_factory;
    $this->entityTypeManager = $entityTypeManager;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
    $this->state = \Drupal::state();
  }

  /**
   * Get a list of allowed entity types.
   */
  public function getAllowedEntities() {
    return ['node_type', 'taxonomy_vocabulary'];
  }

  /**
   * Check if an entity type is allowed to be the frontpage.
   *
   * @param string $entity_type
   *   The entity type.
   */
  public function entityTypeIsAllowed($entity_type) {
    return in_array($entity_type, $this->getAllowedEntities());
  }

  /**
   * Check if a content have to show the set frontpage options.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   */
  public function entityCanBeFrontPage(EntityInterface $entity) {
    if ($entity instanceof NodeInterface) {
      $key = 'set_front_page_node_type__' . $entity->getType();
    }
    elseif ($entity instanceof TermInterface) {
      $key = 'set_front_page_taxonomy_vocabulary__' . $entity->bundle();
    }
    else {
      return FALSE;
    }

    $config = $this->getConfig();
    if (isset($config['types']) && in_array($key, $config['types']) && $config['types'][$key]) {
      return TRUE;
    }
  }

  /**
   * Store set_front_page configuration.
   *
   * @param string $frontpage
   *   The frontpage path.
   * @param string $default
   *   The default path for the frontpage.
   * @param array $bundles
   *   List of all the bundles allowed and not allowed.
   */
  public function saveConfig(string $frontpage = NULL, string $default = NULL, array $bundles = NULL) {
    // Retrieve the configuration.
    $config = $this->config->getEditable('set_front_page.settings');;

    if ($frontpage !== NULL) {
      // frontpage can be empty
      $this->setFrontPage($frontpage);
    }

    if ($default !== NULL) {
      // default can be empty
      $config->set('site_frontpage_default', $default);
    }

    if ($bundles) {
      $types = [];
      foreach ($bundles as $value) {
        $key = 'set_front_page_' . $value['entity_type'] . '__' . $value['bundle'];
        $types[$key] = $value['status'];
      }
      $config->set('types', $types);
    }

    $config->save();

    // Changing the site settings may mean a different route is selected for the
    // front page. Additionally a change to the site name or similar must
    // invalidate the render cache since this could be used anywhere.
    $this->cacheTagsInvalidator->invalidateTags(['route_match', 'rendered']);
  }

  /**
   * Return the current configuration.
   */
  public function getConfig() {
    $config = $this->config->get('set_front_page.settings');
    $default = $config->get('site_frontpage_default');
    $types = $config->get('types');
    $frontpage = $this->getFrontPage();

    return ['frontpage' => $frontpage, 'default' => $default, 'types' => $types];
  }

  /**
   * Return the frontpage path value.
   */
  public function getFrontPage() {
    return $this->state->get('set_front_page.path');
  }

  /**
   * The frontpage value is stored using State api.
   *
   * @param string $path
   *   The frontpage path.
   */
  public function setFrontPage($path) {
    $this->state->set('set_front_page.path', $path);
  }

}
