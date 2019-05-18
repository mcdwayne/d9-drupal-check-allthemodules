<?php

namespace Drupal\drupal_inquicker\Inquicker;

use Drupal\drupal_inquicker\Utilities\Collection;

/**
 * A collection of rows from the Inquicker API.
 */
class RowCollection extends Collection {

  /**
   * Constructor.
   *
   * @param array $data
   *   Data from https://api.inquicker.com/v2/locations.
   */
  public function __construct(array $data) {
    parent::__construct($data);
    $this->data = $data;
    foreach ($data as $location) {
      $this->add([new Row($location)]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function itemClass() : string {
    return Row::class;
  }

}
