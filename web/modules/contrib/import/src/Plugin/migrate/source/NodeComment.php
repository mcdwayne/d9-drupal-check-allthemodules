<?php

/**
 * @file
 * Contains \Drupal\import\Plugin\migrate\source\NodeComment.
 */

namespace Drupal\import\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;

/**
 * Source for node Comment CSV.
 *
 * @MigrateSource(
 *   id = "node_comment"
 * )
 */
class NodeComment extends CSV {

  public function prepareRow(Row $row) {
    // Provide the Default comment settings.
    $row->setDestinationProperty('entity_type', 'node');
    $row->setDestinationProperty('field_name', 'comment');
  }

}
