<?php

namespace Drupal\past_db;

use \Drupal\past\PastEventDataInterface;

/**
 * Past event data entity.
 */
class PastEventData implements PastEventDataInterface {

  public $data_id;
  public $argument_id;
  public $name;
  public $type;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->data_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }
}
