<?php

namespace Drupal\social_link_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'social_links' entity field type.
 *
 * @FieldType(
 *   id = "social_links",
 *   label = @Translation("Social Links"),
 *   description = @Translation("An entity field with social links."),
 *   default_widget = "social_links",
 *   default_formatter = "font_awesome",
 * )
 */
class SocialLinkItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['social'] = DataDefinition::create('string')
      ->setLabel(t('Social network'))
      ->setRequired(FALSE);
    $properties['link'] = DataDefinition::create('string')
      ->setLabel(t('Profile link'))
      ->setRequired(FALSE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema['columns'] = [
      'social' => [
        'type' => 'varchar',
        'length' => 255,
      ],
      'link' => [
        'type' => 'varchar',
        'length' => 255,
      ],
    ];

    return $schema;
  }

}
