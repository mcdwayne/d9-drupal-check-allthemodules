<?php

namespace Drupal\carerix_form\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'carerix_form' field type.
 *
 * @FieldType(
 *   id = "carerix_form",
 *   label = @Translation("Carerix form"),
 *   description = @Translation("This field embeds a Carerix form config entity."),
 *   category = @Translation("Carerix"),
 *   default_formatter = "carerix_form_default",
 *   default_widget = "carerix_form_default",
 * )
 */
class CarerixFormItem extends FieldItemBase implements FieldItemInterface {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'carerix_form_id' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'pub_id' => [
          'type' => 'int',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = [];
    $properties['carerix_form_id'] = DataDefinition::create('string')
      ->setLabel(t('Carerix form'));
    $properties['pub_id'] = DataDefinition::create('integer')
      ->setLabel(t('Carerix publication id'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $item = $this->getValue();
    return empty($item['carerix_form_id']) && empty($item['pub_id']);
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'carerix_form_id';
  }

}
