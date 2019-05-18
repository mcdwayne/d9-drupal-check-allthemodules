<?php

namespace Drupal\oh_regular;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for Oh Map entity.
 */
interface OhRegularMapInterface extends ConfigEntityInterface {

  /**
   * Get the entity type ID for the mapping.
   *
   * @return string
   *   The entity type ID.
   */
  public function getMapEntityType(): string;

  /**
   * Get the bundle for the mapping.
   *
   * @return string
   *   The bundle.
   */
  public function getMapBundle(): string;

  /**
   * Get regular field configuration.
   *
   * @return array
   *   Array of field mapping.
   */
  public function getRegularFields(): array;

  /**
   * Set regular fields.
   *
   * @param array $regularFields
   *   Regular field configuration.
   */
  public function setRegularFields(array $regularFields);

}
