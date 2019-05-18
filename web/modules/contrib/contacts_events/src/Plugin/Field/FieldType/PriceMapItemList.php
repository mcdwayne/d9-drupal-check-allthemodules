<?php

namespace Drupal\contacts_events\Plugin\Field\FieldType;

use Drupal\contacts_events\Entity\EventClass;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;

/**
 * Represents the booking windows field.
 */
class PriceMapItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\contacts_events\Plugin\Field\FieldType\PriceMapItem[]
   */
  protected $list = [];

  /**
   * The price map.
   *
   * @var \Drupal\contacts_events\Plugin\Field\FieldType\PriceMapItem[][]
   *
   * @see \Drupal\contacts_events\Plugin\Field\FieldType\PriceMapItemList::getPriceMap
   */
  protected $map;

  /**
   * Build an associative array containing the price mapping.
   *
   * @return \Drupal\contacts_events\Plugin\Field\FieldType\PriceMapItem[][]
   *   An array with outer keys of booking window ID and class ID. The delta for
   *   each item is retrievable by calling $item->getName().
   */
  public function getPriceMap() {
    if (!isset($this->map)) {
      $this->map = [];
      foreach ($this->list as $delta => $item) {
        $this->map[$item->getBookingWindow()][$item->getClass()] = $item;
      }
    }
    return $this->map;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) {
    return [];
  }

  /**
   * Get the booking windows for this price map.
   *
   * @return \Drupal\contacts_events\Plugin\Field\FieldType\BookingWindowsItemList|null
   *   The booking windows or NULL if we can't get any.
   */
  public function getBookingWindows() {
    $field_name = $this->getFieldDefinition()->getSetting('booking_window_field');
    $entity = $this->getEntity();
    return ($field_name && $entity->hasField($field_name)) ?
      $entity->get($field_name)->filterEmptyItems() :
      NULL;
  }

  /**
   * Get the classes for this price map.
   *
   * @return \Drupal\contacts_events\Entity\EventClassInterface[]
   *   The sorted classes.
   */
  public function getClasses() {
    $field_name = $this->getFieldDefinition()->getSetting('class_field');
    $entity = $this->getEntity();
    $classes = ($field_name && $entity->hasField($field_name)) ?
      $entity->get($field_name)->filterEmptyItems()->referencedEntities() :
      [];
    uasort($classes, [EventClass::class, 'sort']);
    return $classes;
  }

}
