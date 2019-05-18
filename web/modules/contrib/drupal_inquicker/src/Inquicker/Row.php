<?php

namespace Drupal\drupal_inquicker\Inquicker;

/**
 * A row from the Inquicker API.
 */
class Row {

  /**
   * Constructor.
   *
   * @param array $data
   *   Row data.
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

  /**
   * Get the row ID.
   *
   * @return string
   *   The row ID.
   */
  public function id() : string {
    return $this->data['id'];
  }

}
