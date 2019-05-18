<?php

/*
 * mm_grouplist field type.
 */

namespace Drupal\mm_fields\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * @FieldType(
 *  id = "mm_grouplist",
 *  label = @Translation("MM Group List"),
 *  description = @Translation("Contains a list of MM groups. The data is stored in an integer field."),
 *  default_widget = "mm_grouplist",
 *  default_formatter = "mm_fields_link_page",
 * )
 */
class MMGrouplist extends MMCatlist {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(t('Groups'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $values['value'] = [mm_content_groups_mmtid() => mm_content_get_name(mm_content_groups_mmtid())];
    return $values;
  }

}