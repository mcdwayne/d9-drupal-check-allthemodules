<?php

namespace Drupal\Driver\Database\oracle;

use Drupal\Core\Database\Query\Upsert as QueryUpsert;

/**
 * Oracle implementation of \Drupal\Core\Database\Query\Upsert.
 */
class Upsert extends QueryUpsert {

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    // @TODO: handle this?
    return '';
  }

}
