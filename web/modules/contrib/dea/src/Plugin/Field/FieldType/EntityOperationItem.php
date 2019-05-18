<?php

namespace Drupal\dea\Plugin\Field\FieldType;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Field\Annotation\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Entity\EntityInterface;

/**
 * @FieldType(
 *   id = "entity_operation",
 *   label = @Translation("Entity operation"),
 *   description = @Translation("Stores entity operation keys."),
 *   default_widget = "entity_operation",
 *   default_formatter = "entity_operation",
 *   list_class = "\Drupal\dea\Plugin\Field\FieldType\EntityOperationItemList"
 * )
 */
class EntityOperationItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return !isset($this->values['entity_type']) || $this->values['entity_type'] == '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['entity_type'] = DataDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setRequired(TRUE);

    $properties['bundle'] = DataDefinition::create('string')
      ->setLabel(t('Bundle'))
      ->setRequired(TRUE);

    $properties['operation'] = DataDefinition::create('string')
      ->setLabel(t('Operation'))
      ->setRequired(TRUE);

    return $properties;
  }

  public function matches(EntityInterface $entity, $operation) {
    return
      $entity->getEntityTypeId() == $this->values['entity_type']
      && $entity->bundle() == $this->values['bundle']
      && $operation == $this->values['operation'];
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'entity_type' => array(
          'type' => 'varchar',
          'length' => 128,
        ),
        'bundle' => array(
          'type' => 'varchar',
          'length' => 128,
        ),
        'operation' => array(
          'type' => 'varchar',
          'length' => 128,
        ),
      ),
      'indexes' => array(
        'entity_operation' => array('entity_type', 'operation'),
      ),
    );
  }
}