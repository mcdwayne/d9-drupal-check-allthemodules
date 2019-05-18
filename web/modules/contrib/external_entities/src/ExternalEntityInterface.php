<?php

namespace Drupal\external_entities;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines a common interface for all external entity objects.
 */
interface ExternalEntityInterface extends ContentEntityInterface {

  /**
   * Defines the field name used to reference the optional annotation entity.
   */
  const ANNOTATION_FIELD = 'annotation';

  /**
   * Defines the prefix of annotation fields inherited by the external entity.
   */
  const ANNOTATION_FIELD_PREFIX = 'annotation_';

  /**
   * Gets the external entity type.
   *
   * @return \Drupal\external_entities\ExternalEntityTypeInterface
   *   The external entity type.
   */
  public function getExternalEntityType();

  /**
   * Extract raw data from this entity.
   *
   * @return array
   *   The raw data array.
   */
  public function extractRawData();

  /**
   * Gets the associated annotation entity.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface|null
   *   The annotation entity, null otherwise.
   */
  public function getAnnotation();

  /**
   * Map the annotations entity fields to this entity.
   *
   * @return $this
   */
  public function mapAnnotationFields();

}
