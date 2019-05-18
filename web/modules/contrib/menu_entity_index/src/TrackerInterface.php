<?php

namespace Drupal\menu_entity_index;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines an interface for classes tracking entities referenced by menu links.
 */
interface TrackerInterface {

  /**
   * Deletes all database records for the given host entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The deleted host entity.
   */
  public function deleteEntity(EntityInterface $entity);

  /**
   * Gets Content Entity Type Ids, that are available for tracking.
   *
   * @return array
   *   Entity Type Ids available for tracking.
   */
  public function getAvailableEntityTypes();

  /**
   * Gets Menu Names, that are available for tracking.
   *
   * @return array
   *   Menu Names available for tracking.
   */
  public function getAvailableMenus();

  /**
   * Gets host information for a target entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The target entity to get the host information for.
   *
   * @return array
   *   Host information for target entity. For each menu link that references
   *   that entity, an array value will be returned. That value is an array with
   *   the following keys:
   *     - menu_name: Menu name of menu link referencing the entity.
   *     - level: Menu level of menu link referencing the entity.
   *     - label: Label of menu link referencing the entity.
   *     - link: URL object for edit page of menu link referencing the entity.
   *         If the current user does not have access to view the menu link, the
   *         key contains an empty string instead.
   *     - language: Name of language of menu link referencing the entity.
   */
  public function getHostData(EntityInterface $entity);

  /**
   * Gets entity types configured for tracking.
   *
   * @return array
   *   Entity Type Ids to tack.
   */
  public function getTrackedEntityTypes();

  /**
   * Gets menus configured for tracking.
   *
   * @return array
   *   Menu names to tack.
   */
  public function getTrackedMenus();

  /**
   * Checks, if an entity type is among the tracked entity types.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $type
   *   Entity type to check.
   *
   * @return bool
   *   TRUE, if entity type is among tracked entity types. Otherwise FALSE.
   */
  public function isTrackedEntityType(EntityTypeInterface $type);

  /**
   * Gets stored configuration object.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   A configuration object.
   */
  public function getConfiguration();

  /**
   * Sets configuration values and triggers rescanning of menus as needed.
   *
   * Updates the tracker service configuration with the new values. If menus or
   * entity types are to be removed, database records will be deleted as needed.
   * If menus or entity types are added, a batch process will be initiated to
   * rescan and add database records for menu links as needed.
   *
   * @param array $form_values
   *   The submitted form values of the configuration form.
   * @param bool $force_rebuild
   *   Whether or not to force a rebuild for all menu's.
   */
  public function setConfiguration(array $form_values = [], $force_rebuild = FALSE);

  /**
   * Updates database tracking for new or updated entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The updated host entity.
   */
  public function updateEntity(EntityInterface $entity);

}
