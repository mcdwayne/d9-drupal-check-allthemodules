<?php

namespace Drupal\bigcommerce\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'bigcommerce_id' entity field type.
 *
 * @FieldType(
 *   id = "bigcommerce_id",
 *   label = @Translation("BigCommerce ID"),
 *   description = @Translation("An entity field containing the BigCommerce ID for a synced entity."),
 *   no_ui = TRUE,
 *   list_class = "\Drupal\bigcommerce\Plugin\Field\FieldType\BigCommerceIdFieldItemList",
 * )
 */
class BigCommerceId extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('BigCommerce ID'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['value'] = mt_rand(1, 1000);
    return $values;
  }

}
