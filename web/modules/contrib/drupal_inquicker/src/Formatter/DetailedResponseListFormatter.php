<?php

namespace Drupal\drupal_inquicker\Formatter;

use Drupal\drupal_inquicker\traits\Singleton;

/**
 * Formats a RowCollection as an array of detailed info keyed by IDs.
 */
class DetailedResponseListFormatter extends AbstractResponseListFormatter {

  use Singleton;

  /**
   * {@inheritdoc}
   */
  public function formatIndividualItem(object $item) {
    return $item->data;
  }

}
