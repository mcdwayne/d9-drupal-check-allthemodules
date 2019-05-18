<?php

namespace Drupal\field_menu\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_menu' field type.
 *
 * @FieldType(
 *   id = "field_menu",
 *   label = @Translation("Menu Item"),
 *   module = "field_menu",
 *   description = @Translation("Select a valid Menu item"),
 *   default_widget = "field_menu_tree_widget",
 *   default_formatter = "field_menu_tree_formatter"
 * )
 */
class MenuItemId extends FieldItemBase {

  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'menu_title' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'menu_item_key' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'max_depth' => [
          'type' => 'int',
          'unsigned' => FALSE,
          'size' => 'small',
          'not null' => FALSE,
        ],
        'include_root' => [
          'type' => 'int',
          'unsigned' => FALSE,
          'size' => 'tiny',
          'not null' => FALSE,
        ],
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('menu_item_key')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['menu_title'] = DataDefinition::create('string')->setLabel(t('Title'));
    $properties['menu_item_key'] = DataDefinition::create('string')->setLabel(t('Menu Item'));
    $properties['include_root'] = DataDefinition::create('integer')->setLabel(t('Include root'));
    $properties['max_depth'] = DataDefinition::create('integer')->setLabel(t('Max depth'));

    return $properties;
  }

}
