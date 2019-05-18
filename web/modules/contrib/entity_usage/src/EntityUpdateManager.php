<?php

namespace Drupal\entity_usage;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Class EntityUpdateManager.
 *
 * @package Drupal\entity_usage
 */
class EntityUpdateManager {

  /**
   * The usage tracking service.
   *
   * @var \Drupal\entity_usage\EntityUsage $usage_service
   */
  protected $usageService;

  /**
   * The PluginManager track service.
   *
   * @var \Drupal\entity_usage\EntityUsageTrackManager $TrackManager
   */
  protected $trackManager;

  /**
   * EntityUpdateManager constructor.
   *
   * @param \Drupal\entity_usage\EntityUsage $usage_service
   *   The usage tracking service.
   * @param \Drupal\entity_usage\EntityUsageTrackManager $track_manager
   *   The PluginManager track service.
   */
  public function __construct(
    EntityUsage $usage_service,
    EntityUsageTrackManager $track_manager
  ) {
    $this->usageService = $usage_service;
    $this->trackManager = $track_manager;
  }

  /**
   * Track updates on creation of potential host entities.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity we are dealing with.
   */
  public function trackUpdateOnCreation(ContentEntityInterface $entity) {

    // Only act on content entities.
    if (!($entity instanceof ContentEntityInterface)) {
      return;
    }

    // Call all plugins that want to track entity usages.
    foreach ($this->trackManager->getDefinitions() as $plugin_id => $plugin_definition) {
      /** @var EntityUsageTrackInterface $instance */
      $instance = $this->trackManager->createInstance($plugin_id);
      $instance->trackOnEntityCreation($entity);
    }

  }

  /**
   * Track updates on deletion of potential host entities.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity we are dealing with.
   */
  public function trackUpdateOnDeletion(ContentEntityInterface $entity) {

    // Only act on content entities.
    if (!($entity instanceof ContentEntityInterface)) {
      return;
    }

    // Call all plugins that want to track entity usages.
    foreach ($this->trackManager->getDefinitions() as $plugin_id => $plugin_definition) {
      /** @var EntityUsageTrackInterface $instance */
      $instance = $this->trackManager->createInstance($plugin_id);
      $instance->trackOnEntityDeletion($entity);
    }

    // Now clean the possible usage of the entity that was deleted when target.
    $this->usageService->delete($entity->id(), $entity->getEntityTypeId());

  }

  /**
   * Track updates on edit / update of potential host entities.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity we are dealing with.
   */
  public function trackUpdateOnEdition(ContentEntityInterface $entity) {

    // Only act on content entities.
    if (!($entity instanceof ContentEntityInterface)) {
      return;
    }

    // Call all plugins that want to track entity usages.
    foreach ($this->trackManager->getDefinitions() as $plugin_id => $plugin_definition) {
      /** @var EntityUsageTrackInterface $instance */
      $instance = $this->trackManager->createInstance($plugin_id);
      $instance->trackOnEntityUpdate($entity);
    }

  }

}
