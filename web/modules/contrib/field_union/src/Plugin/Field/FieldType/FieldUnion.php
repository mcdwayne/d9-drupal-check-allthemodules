<?php

namespace Drupal\field_union\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\Plugin\PluginBase;
use Drupal\entity_test\FieldStorageDefinition;
use Drupal\field_union\TypedData\FieldProxyDataDefinition;

/**
 * Plugin implementation of the 'link' field type.
 *
 * @FieldType(
 *   id = "field_union",
 *   deriver = "\Drupal\field_union\Plugin\Derivative\FieldUnionDeriver",
 *   label = @Translation("Field Union"),
 *   default_widget = "field_union",
 *   default_formatter = "field_union",
 * )
 */
class FieldUnion extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  const SEPARATOR = '__';

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $union = self::getFieldUnionEntityFromFieldDefinition($field_definition);
    $definitions = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
    $properties = [];
    foreach ($union->getFields() as $field_name => $field) {
      if (isset($field['field_type']) && isset($definitions[$field['field_type']])) {
        $field_type = $field['field_type'];
        $definition = $definitions[$field_type];
        $subfield_definition = FieldStorageDefinition::create($field['field_type'])
          ->setSettings($field['settings'])
          ->setName($field_name)
          ->setTargetEntityTypeId($field_definition->getTargetEntityTypeId());
        $field_map = FieldProxyDataDefinition::create('field_union_field_proxy')
          ->setLabel($field['name'])
          ->setComputed(TRUE)
          ->setProxyFieldType($field['field_type'])
          ->setRequired(!empty($field['required']));
        $subfield_properties = $definition['class']::propertyDefinitions($subfield_definition);
        $field_map->setPropertyDefinition('proxy', FieldItemDataDefinition::createFromDataType('field_item:' . $field['field_type'])->setSettings($field['settings']));
        if ($main_property = $definition['class']::mainPropertyName()) {
          $field_map->setMainPropertyName($main_property);
        }
        foreach ($subfield_properties as $name => $property) {
          $class = get_class($property);
          $properties[$field_name . self::SEPARATOR . $name] = new $class($property->toArray() + [
            'linked_parent_name' => $field_name,
            'linked_property_name' => $name,
          ]);
        }
        $properties[$field_name] = $field_map;
      }
    }
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $union = self::getFieldUnionEntityFromFieldDefinition($field_definition);
    $columns = $indexes = [];
    $definitions = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
    foreach ($union->getFields() as $field_name => $field) {
      if (isset($field['field_type']) && isset($definitions[$field['field_type']])) {
        $field_type = $field['field_type'];
        $definition = $definitions[$field_type];
        $subfield_definition = FieldStorageDefinition::create($field['field_type'])
          ->setSettings($field['settings'])
          ->setName($field_name)
          ->setTargetEntityTypeId($field_definition->getTargetEntityTypeId());
        $subfield_schema = $definition['class']::schema($subfield_definition);

        foreach ($subfield_schema['columns'] as $name => $property) {
          $columns[$field_name . self::SEPARATOR . $name] = $property;
        }
        if (empty($subfield_schema['indexes'])) {
          continue;
        }

        foreach ($subfield_schema['indexes'] as $name => $index) {
          $indexes[$field_name . self::SEPARATOR . $name] = self::prepareIndex($index, $field_name);
        }

      }
    }
    return [
      'columns' => $columns,
      'indexes' => $indexes,
    ];
  }

  /**
   * Prepares index.
   *
   * @param array $index
   *   Index details.
   * @param string $field_name
   *   Field name.
   *
   * @return array
   *   Prepared index.
   */
  protected static function prepareIndex(array $index, $field_name) {
    foreach ($index as $ix => $item) {
      if (is_string($item)) {
        $index[$ix] = $field_name . self::SEPARATOR . $item;
        continue;
      }
      $index[$ix][0] = $field_name . self::SEPARATOR . $index[$ix][0];
    }
    return $index;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $union = self::getFieldUnionEntityFromFieldDefinition($field_definition->getFieldStorageDefinition());
    $definitions = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();
    $values = [];
    foreach ($union->getFields() as $field_name => $field) {
      if (isset($field['field_type']) && isset($definitions[$field['field_type']])) {
        $field_type = $field['field_type'];
        $definition = $definitions[$field_type];
        $storage_definition = FieldStorageDefinition::create($field['field_type'])
          ->setSettings($field['settings'])
          ->setName($field_name)
          ->setTargetEntityTypeId($field_definition->getTargetEntityTypeId());
        $subfield_definition = FieldDefinition::createFromFieldStorageDefinition($storage_definition);

        $values[$field_name] = $definition['class']::generateSampleValue($subfield_definition);
      }
    }
    return $values;
  }

  /**
   * Gets field union entity from field definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_definition
   *   Field definition.
   *
   * @return \Drupal\field_union\Entity\FieldUnionInterface
   *   Field union.
   */
  protected static function getFieldUnionEntityFromFieldDefinition(FieldStorageDefinitionInterface $field_definition) {
    $type = $field_definition->getType();
    list(, $union_id) = explode(PluginBase::DERIVATIVE_SEPARATOR, $type);
    $union = \Drupal::entityTypeManager()
      ->getStorage('field_union')
      ->load($union_id);
    return $union;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    foreach ($this->definition->getPropertyDefinitions() as $name => $propertyDefinition) {
      if (!$propertyDefinition instanceof FieldProxyDataDefinition || $this->get($name)->isEmpty()) {
        continue;
      }
      return FALSE;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    /** @var \Drupal\Core\TypedData\DataDefinition $propertyDefinition */
    foreach ($this->definition->getPropertyDefinitions() as $name => $propertyDefinition) {
      if (!$propertyDefinition instanceof FieldProxyDataDefinition && isset($values[$name])) {
        $definition = $propertyDefinition->toArray();
        if (isset($definition['linked_property_name']) && isset($definition['linked_parent_name'])) {
          $values[$definition['linked_parent_name']][$definition['linked_property_name']] = $values[$name];
        }
      }
    }
    parent::setValue($values, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();
    foreach ($this->definition->getPropertyDefinitions() as $name => $propertyDefinition) {
      if ($propertyDefinition instanceof FieldProxyDataDefinition) {
        $this->properties[$name]->preSave();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __set($name, $value) {
    parent::__set($name, $value);
    $properties = $this->definition->getPropertyDefinitions();
    if (isset($properties[$name])) {
      $property = $properties[$name];
      if ($property instanceof FieldProxyDataDefinition && $property->getMainPropertyName()) {
        // Update the linked property too.
        parent::__set($name . self::SEPARATOR . $property->getMainPropertyName(), $value);
      }
    }
  }

}
