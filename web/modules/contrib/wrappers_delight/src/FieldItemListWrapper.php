<?php

namespace Drupal\wrappers_delight;

use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;

class FieldItemListWrapper implements \Countable, \ArrayAccess, AccessibleInterface {

  /**
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  protected $list;

  /**
   * FieldItemListWrapper constructor.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $list
   */
  protected function __construct(FieldItemListInterface $list) {
    $this->list = $list;
  }

  /**
   * @param \Drupal\Core\Field\FieldItemListInterface $list
   *
   * @return static
   */
  public static function wrap(FieldItemListInterface $list) {
    return new static($list);
  }

  /**
   * @return \Drupal\Core\Field\FieldItemListInterface
   */
  public function raw() {
    return $this->list;
  }

  /**
   * @return \Drupal\wrappers_delight\FieldItemWrapper[]
   */
  public function toArray() {
    $values = [];
    foreach ($this->list as $i => $item) {
      $values[$i] = \Drupal::service('plugin.manager.wrappers_delight')->wrapField($item);
    }
    return $values;
  }

  /**
   * @return \Drupal\wrappers_delight\FieldItemWrapper|NULL
   */
  public function first() {
    if (!$this->isEmpty()) {
      return \Drupal::service('plugin.manager.wrappers_delight')->wrapField($this->list->first());
    }
    return NULL;
  }

  /**
   * @param array $display_options
   *
   * @return array
   */
  public function view($display_options) {
    return $this->list->view($display_options);
  }

  /**
   * @return bool
   */
  public function isEmpty() {
    return $this->list->isEmpty();
  }

  /**
   * @return int The custom count as an integer.
   */
  public function count() {
    return $this->list->count();
  }

  /**
   * @return boolean true on success or false on failure.
   */
  public function offsetExists($offset) {
    return $this->list->offsetExists($offset);
  }

  /**
   * @param mixed $offset The offset to retrieve.
   * 
   * @return \Drupal\wrappers_delight\FieldItemWrapper[]
   */
  public function offsetGet($offset) {
    return \Drupal::service('plugin.manager.wrappers_delight')->wrapField($this->list->offsetGet($offset));
  }

  /**
   * @param mixed $offset The offset to assign the value to.
   * @param mixed $value The value to set.
   */
  public function offsetSet($offset, $value) {
    if ($value instanceof FieldItemWrapper) {
      $this->list->offsetSet($offset, $value->raw());
    }
    else {
      $this->list->offsetSet($offset, $value);
    }
  }

  /**
   * @param mixed $offset The offset to unset.
   */
  public function offsetUnset($offset) {
    $this->list->offsetUnset($offset);
  }

  /**
   * @inheritDoc
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    return $this->list->access($operation, $account, $return_as_object);
  }

}
