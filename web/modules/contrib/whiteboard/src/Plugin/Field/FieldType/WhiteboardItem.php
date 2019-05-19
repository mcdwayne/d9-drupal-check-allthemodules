<?php

/**
 * @file
 * Contains \Drupal\whiteboard\Plugin\Field\FieldType\WhiteboardItem.
 */

namespace Drupal\whiteboard\Plugin\Field\FieldType;

use Drupal\whiteboard\Whiteboard;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Plugin implementation of the 'whiteboard' field type.
 *
 * @FieldType(
 *   id = "whiteboard",
 *   label = @Translation("Whiteboard"),
 *   description = @Translation("This references a Whiteboard."),
 *   default_widget = "whiteboard_reference",
 * )
 */
class WhiteboardItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'wbid' => array(
          'type' => 'int',
          'not null' => FALSE,
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['wbid'] = DataDefinition::create('integer')
        ->setLabel(t('Whiteboard Id'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
   public function isEmpty() {
     // $value = $this->get('cid')->getValue();
     // return $value === NULL || $value === '';
     return !empty($this->values['whiteboard']['whiteboard_delete']);
   }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $user = \Drupal::currentUser();

    $whiteboard = new Whiteboard();
    $whiteboard->set('title', $this->values['whiteboard']['whiteboard_title']);
    $whiteboard->set('uid', $user->id());
    $whiteboard->set('marks', $this->values['whiteboard']['whiteboard_title']);
    if (isset($this->values['whiteboard']['whiteboard_format'])) {
      $whiteboard->set('format', $this->values['whiteboard']['whiteboard_format']);
    }
    $whiteboard->save();
    $this->set('wbid', $whiteboard->get('wbid'));
  }

  /**
   * {@inheritdoc}
   */
  public function update() {
    $user = \Drupal::currentUser();

    if (is_array($this->values['whiteboard'])) {
      $whiteboard = new Whiteboard($this->values['whiteboard']['whiteboard_wbid']);
      $whiteboard->set('title', $this->values['whiteboard']['whiteboard_title']);
      $whiteboard->set('uid', $user->id());
      if (isset($this->values['whiteboard']['whiteboard_format'])) {
        $whiteboard->set('format', $this->values['whiteboard']['whiteboard_format']);
      }
      $whiteboard->save();
      $this->set('wbid', $whiteboard->get('wbid'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    $tables = db_find_tables('whiteboard');
    foreach ($tables as $table) {
      $sql = 'DELETE FROM {' . $table . '} WHERE wbid = :wbid';
      db_query($sql, array(':wbid' => $this->get('wbid')->getValue()));
    }
  }

}
