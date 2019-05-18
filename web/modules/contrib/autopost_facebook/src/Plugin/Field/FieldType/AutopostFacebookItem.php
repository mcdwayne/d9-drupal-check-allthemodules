<?php

namespace Drupal\autopost_facebook\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'autopost_facebook' field type.
 *
 * @FieldType(
 *   id = "autopost_facebook",
 *   label = @Translation("Autopost Facebook"),
 *   description = @Translation("An entity field triggering posting on Facebook."),
 *   default_widget = "autopost_facebook_default",
 *   default_formatter = "autopost_facebook_likes_count",
 *   cardinality = 1,
 * )
 */
class AutopostFacebookItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'int',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('Integer value'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->value) && (string) $this->value !== '0';
  }

}
