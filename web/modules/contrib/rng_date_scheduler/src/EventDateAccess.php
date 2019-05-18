<?php

/**
 * @file
 * Contains \Drupal\rng_date_scheduler\EventDateAccess.
 */

namespace Drupal\rng_date_scheduler;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Event date access.
 */
class EventDateAccess {

  protected $fieldName;

  /**
   * @var \Drupal\Core\Datetime\DrupalDateTime $date
   */
  protected $date;

  protected $access_before;
  protected $access_after;

  function getFieldName() {
    return $this->fieldName;
  }

  function setFieldName($field_name) {
    $this->fieldName = $field_name;
    return $this;
  }

  function getDate() {
    return $this->date;
  }

  function setDate(DrupalDateTime $date) {
    $this->date = $date;
    return $this;
  }

  function canAccessBefore() {
    return $this->access_before;
  }

  function setAccessBefore($access_before) {
    $this->access_before = $access_before;
    return $this;
  }

  function canAccessAfter() {
    return $this->access_after;
  }

  function setAccessAfter($access_after) {
    $this->access_after = $access_after;
    return $this;
  }


}