Module Overview

This module provides a Hexidecimal Color Field API Field. The field collects and
stores hexidecimal color strings, in the format #XXXXXX where X is a hexidecimal
(0-9, a-f) character.

The module also provides a hexidecimal_string TypedData API data type. This can
then be used in the Field API by defining a property as a hexidecimal_color. For
example, in a class that extends FieldItemBase, the propertyDefitions() method
would look something like this:

/**
 * {@inheritdoc}
 */
public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

  $properties['color'] = DataDefinition::create('hexidecimal_color')
    ->setLabel(t('Hexidecimal color'));

  return $properties;
}

Modules that provide widgets for this field type:

- Jquery Colorpicker (https://www.drupal.org/project/jquery_colorpicker)
