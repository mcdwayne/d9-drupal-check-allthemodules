<?php

namespace Drupal\contacts_events\Plugin\Field\FieldType;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;

/**
 * Represents the booking windows field.
 */
class BookingWindowsItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\contacts_events\Plugin\Field\FieldType\BookingWindowsItem[]
   */
  protected $list = [];

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    $constraints[] = $this->getTypedDataManager()
      ->getValidationConstraintManager()
      ->create('BookingWindowsUnique', []);

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    parent::preSave();
    usort($this->list, [$this, 'sortItems']);
  }

  /**
   * Sorting callback for booking windows.
   *
   * Will sort by cut off ascending, with NULL cut off last.
   *
   * @param \Drupal\contacts_events\Plugin\Field\FieldType\BookingWindowsItem $a
   *   The first booking window.
   * @param \Drupal\contacts_events\Plugin\Field\FieldType\BookingWindowsItem $b
   *   The second booking window.
   *
   * @return int
   *   The sort comparison result.
   */
  public static function sortItems(BookingWindowsItem $a, BookingWindowsItem $b) {
    $a_cut_off = $a->cut_off ?? 'A';
    $b_cut_off = $b->cut_off ?? 'A';
    return $a_cut_off <=> $b_cut_off;
  }

  /**
   * A machine name callback for checking the uniqueness of IDs.
   *
   * @param string $value
   *   The ID being checked.
   * @param array $element
   *   The element array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return bool
   *   Whether the ID is unique.
   */
  public static function checkUnique($value, array $element, FormStateInterface $form_state) {
    $parents = $element['#parents'];
    array_pop($parents);
    $delta_checking = array_pop($parents);

    $submitted_values = $form_state->getValue($parents);
    foreach ($submitted_values as $delta => $values) {
      if (in_array($delta, [$delta_checking, 'add_more'])) {
        continue;
      }

      if ($values['id'] == $value) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Find the appropriate window for a specific date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime|null $date
   *   The date to check against, or NULL to use now.
   *
   * @return \Drupal\contacts_events\Plugin\Field\FieldType\BookingWindowsItem|null
   *   The booking window item, or NULL if none is found.
   */
  public function findWindow(DrupalDateTime $date = NULL) {
    if (!isset($date)) {
      $date = new DrupalDateTime();
    }

    // If our precision is by day, set our date to 00:00:00.
    if ($this->getSetting('datetime_type') == DateTimeItem::DATETIME_TYPE_DATE) {
      $date->setTime(0, 0, 0);
    }

    // Ensure booking windows are sorted.
    usort($this->list, [$this, 'sortItems']);

    // Loop through until we find our match.
    foreach ($this->list as $window) {
      if (!$window->cut_off || $window->date >= $date) {
        return $window;
      }
    }

    return NULL;
  }

}
