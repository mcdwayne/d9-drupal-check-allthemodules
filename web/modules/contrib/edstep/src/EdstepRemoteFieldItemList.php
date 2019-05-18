<?php

namespace Drupal\edstep;

use Drupal\Core\Field\FieldItemList;

/**
 * Defines a item list class for edstep fields.
 */
class EdstepRemoteFieldItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    $this->ensurePopulated();
    return new \ArrayIterator($this->list);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    $this->ensurePopulated();
    return parent::getValue($include_computed);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $this->ensurePopulated();
    return parent::isEmpty();
  }

  /**
   * Makes sure that the item list is never empty.
   *
   * For 'normal' fields that use database storage the field item list is
   * initially empty, but since this is a computed field this always has a
   * value.
   * Make sure the item list is always populated, so this field is not skipped
   * for rendering in EntityViewDisplay and friends.
   *
   * @todo This will no longer be necessary once #2392845 is fixed.
   *
   * @see https://www.drupal.org/node/2392845
   */
  protected function ensurePopulated() {
    if (empty($this->list)) {
      $entity = $this->getEntity();
      $key = $this->getFieldDefinition()->getName();
      $values = $entity->getRemoteValue($key);
      $values = is_array($values) ? $values : [$values];
      $values = array_filter($values, '\Drupal\edstep\EdstepRemoteFieldItemList::isNotEmptyValue');
      foreach($values as $index => $value) {
        $values = ['value' => $value];
        switch($this->getFieldDefinition()->getType()) {
          case 'text':
          case 'text_long':
            $values['format'] = \Drupal::config('edstep.settings')->get("field_mappings.course.{$key}.format");
            break;
        }
        $this->list[$index] = $this->createItem($index, $values);
      }
    }
  }

  public static function isEmptyValue($value) {
    switch(gettype($value)) {
      case 'string':
        return empty($value);
      case 'NULL':
        return TRUE;
      default:
        return FALSE;
    }
  }

  public static function isNotEmptyValue($value) {
    return !self::isEmptyValue($value);
  }

}
