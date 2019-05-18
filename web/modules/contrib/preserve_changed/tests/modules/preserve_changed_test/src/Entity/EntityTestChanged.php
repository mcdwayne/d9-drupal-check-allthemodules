<?php

namespace Drupal\preserve_changed_test\Entity;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\entity_test\Entity\EntityTest;

/**
 * Provided an entity type such as EntityTest but with a 'changed' field.
 *
 * @ContentEntityType(
 *   id = "entity_test_changed",
 *   label = @Translation("Test entity with changed field"),
 *   base_table = "entity_test_changed",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 * )
 */
class EntityTestChanged extends EntityTest implements EntityChangedInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $definitions = parent::baseFieldDefinitions($entity_type);

    $definitions['changed'] = BaseFieldDefinition::create('changed')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    // Ensure a new timestamp.
    sleep(1);
    parent::save();
  }

}
