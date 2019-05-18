<?php

/**
 * @file
 * Contains \Drupal\entityblock\Plugin\Field\FieldType\EntityBlockItem.
 */

namespace Drupal\entityblock\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'EntityBlock' field type.
 *
 * @FieldType(
 *   id = "entityblock",
 *   label = @Translation("EntityBlock"),
 *   description = @Translation("Transforms an entity into a block."),
 *   default_widget = "entityblock_default",
 *   default_formatter = "entityblock"
 * )
 */
class EntityBlockItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return array(
      'enabled' => 1,
      'title' => DRUPAL_OPTIONAL,
      'view_mode' => 'full',
    ) + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['enabled'] = DataDefinition::create('boolean')->setLabel(t('Enabled'));
    $properties['title'] = DataDefinition::create('string')->setLabel(t('Title'));
    $properties['view_mode'] = DataDefinition::create('string')->setLabel(t('View mode'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'enabled' => array(
          'description' => 'If this entity can be rendered as block.',
          'type' => 'int',
          'size' => 'tiny',
          'not null' => TRUE,
          'unsigned' => TRUE,
          'default value' => 0,
        ),
        'title' => array(
          'description' => 'Block title.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
        'view_mode' => array(
          'description' => 'View mode to render this block.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return !$this->enabled && !$this->title;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    \Drupal::service('plugin.manager.block')->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $ids = \Drupal::entityQuery('block')
      ->condition('plugin', 'entityblock_block:' . $this->getFieldDefinition()->getFieldStorageDefinition()->uuid() . '|' . $this->getEntity()->uuid(), 'STARTS_WITH')
      ->execute();

    $block_storage = \Drupal::entityTypeManager()->getStorage('block');
    $blocks = $block_storage->loadMultiple($ids);
    $block_storage->delete($blocks);
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $view_modes = \Drupal::entityTypeManager()->getViewModes($field_definition->getTargetEntityTypeId());
    $random = new Random();

    $values = array(
      'enabled' => rand(0, 1),
      'title' => $random->sentences(1),
      'view_mode' => array_rand($view_modes),
    );
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'enabled';
  }
}
