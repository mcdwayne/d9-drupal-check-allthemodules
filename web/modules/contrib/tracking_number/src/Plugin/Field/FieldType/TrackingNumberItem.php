<?php

namespace Drupal\tracking_number\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'tracking_number' entity field type.
 *
 * @FieldType(
 *   id = "tracking_number",
 *   label = @Translation("Tracking number"),
 *   description = @Translation("An entity field containing a tracking number with type."),
 *   default_widget = "tracking_number",
 *   default_formatter = "tracking_number_link"
 * )
 */
class TrackingNumberItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
        'type' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Number'))
      ->setDescription(t('The tracking number.'));

    $properties['type'] = DataDefinition::create('string')
      ->setLabel(t('Type (id)'))
      ->setDescription(t("The tracking number's type id (eg. \"usps\")."));

    $properties['type_label'] = DataDefinition::create('string')
      ->setLabel(t('Type (label)'))
      ->setDescription(t("The tracking number's human-readable type label (eg. \"United States Postal Service\")."))
      ->setComputed(TRUE)
      ->setClass('\Drupal\tracking_number\TypeLabelComputed');

    $properties['url'] = DataDefinition::create('any')
      ->setLabel(t('URL (object)'))
      ->setDescription(t('The Drupal\Core\Url object where tracking information for this tracking number can be found.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\tracking_number\UrlComputed');

    $properties['url_string'] = DataDefinition::create('string')
      ->setLabel(t('URL (string)'))
      ->setDescription(t('The URL string where tracking information for this tracking number can be found.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\tracking_number\UrlStringComputed');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

}
