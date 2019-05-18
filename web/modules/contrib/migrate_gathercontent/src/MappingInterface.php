<?php

namespace Drupal\migrate_gathercontent;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a mapping entity.
 */
interface MappingInterface extends ConfigEntityInterface {

  /**
   * Gets the group.
   *
   * @return \Drupal\migrate_gathercontent\Entity\Group
   *   The group entity.
   */
  public function getGroup();

  /**
   * Gets the field mappings.
   *
   * @return array
   *   The field mapping information.
   */
  public function getFieldMappings();

  /**
   * Gets the field mappings.
   *
   * @return boolean
   *   The status of the mapping.
   */
  public function isEnabled();

  /**
   * Returns the migration id.
   *
   * @return string
   *   The full migration id.
   */
  public function getMigrationId();

  /**
   * Gets the migration dependencies.
   *
   * @return array
   *   The migration dependencies.
   */
  public function getMappingDependencies();

}
