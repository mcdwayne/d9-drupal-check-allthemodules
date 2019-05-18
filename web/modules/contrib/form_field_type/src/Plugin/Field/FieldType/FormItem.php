<?php

/**
 * @file
 * Plugin implementation of the Form field type.
 */

namespace Drupal\drupalcreate_form_field_type\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the Form field type.
 *
 * @FieldType(
 *   id = "form",
 *   label = @Translation("Form"),
 *   description = @Translation("Add a contact form."),
 *   category = @Translation("custom"),
 *   default_widget = "formfield_default",
 *   default_formatter = "formfield_default"
 * )
 */
class FormItem extends FieldItemBase {

  /**
   * Defines field item properties.
   *
   * Properties that are required to constitute a valid, non-empty item should
   * be denoted with \Drupal\Core\TypedData\DataDefinition::setRequired().
   *
   * @return \Drupal\Core\TypedData\DataDefinitionInterface[]
   *   An array of property definitions of contained properties, keyed by
   *   property name.
   *
   * @see \Drupal\Core\Field\BaseFieldDefinition
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
                                           ->setLabel(t('Form'));

    return $properties;
  }

  /**
   * Returns the schema for the field.
   *
   * This method is static because the field schema information is needed on
   * creation of the field. FieldItemInterface objects instantiated at that
   * time are not reliable as field settings might be missing.
   *
   * Computed fields having no schema should return an empty array.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return array
   *   An empty array if there is no schema, or an associative array with the
   *   following key/value pairs:
   *   - columns: An array of Schema API column specifications, keyed by column
   *     name. The columns need to be a subset of the properties defined in
   *     propertyDefinitions(). The 'not null' property is ignored if present,
   *     as it is determined automatically by the storage controller depending
   *     on the table layout and the property definitions. It is recommended to
   *     avoid having the column definitions depend on field settings when
   *     possible. No assumptions should be made on how storage engines
   *     internally use the original column name to structure their storage.
   *   - unique keys: (optional) An array of Schema API unique key definitions.
   *     Only columns that appear in the 'columns' array are allowed.
   *   - indexes: (optional) An array of Schema API index definitions. Only
   *     columns that appear in the 'columns' array are allowed. Those indexes
   *     will be used as default indexes. Field definitions can specify
   *     additional indexes or, at their own risk, modify the default indexes
   *     specified by the field-type module. Some storage engines might not
   *     support indexes.
   *   - foreign keys: (optional) An array of Schema API foreign key
   *     definitions. Note, however, that the field data is not necessarily
   *     stored in SQL. Also, the possible usage is limited, as you cannot
   *     specify another field as related, only existing SQL tables,
   *     such as {taxonomy_term_data}.
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type'     => 'char',
          'length'   => 255,
          'not null' => FALSE,
        ),
      ),
      'indexes' => array(
        'value' => array('value'),
      ),
    );
  }

}
