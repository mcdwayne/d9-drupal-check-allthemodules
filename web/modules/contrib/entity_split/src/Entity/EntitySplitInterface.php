<?php

namespace Drupal\entity_split\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface for defining Entity split entities.
 *
 * @ingroup entity_split
 */
interface EntitySplitInterface extends ContentEntityInterface {

  /**
   * Returns the entity to which this split is attached.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity to which this split is attached.
   */
  public function getMasterEntity();

  /**
   * Returns the type of the entity to which this split is attached.
   *
   * @return string
   *   The type of the entity to which this split is attached.
   */
  public function getMasterEntityType();

  /**
   * Returns the ID of the entity to which this split is attached.
   *
   * @return int
   *   The ID of the entity to which this split is attached.
   */
  public function getMasterEntityId();

  /**
   * Returns entity split entities for the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\entity_split\Entity\EntitySplitInterface[]
   *   List of entity splits attached to the entity keyed by split type.
   */
  public static function getEntitySplitsForEntity(ContentEntityInterface $entity);

  /**
   * Deletes entity split entities for the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   */
  public static function deleteEntitySplitsForEntity(ContentEntityInterface $entity);

  /**
   * Creates proper entity split entities for the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   */
  public static function createRequiredEntitySplitsForEntity(ContentEntityInterface $entity);

  /**
   * Add entity split edition links on the entity form.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string $langcode
   *   The language code identifying the entity translation.
   */
  public static function alterEntityForm(array &$form, ContentEntityInterface $entity, $langcode);

}
