<?php

/**
 * @file
 * Contains \Drupal\import\Plugin\migrate\source\ArticleNode.
 */

namespace Drupal\import\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV;
/**
 * Source for Article node CSV.
 *
 * @MigrateSource(
 *   id = "article_node"
 * )
 */
class ArticleNode extends CSV {
  public function prepareRow(Row $row) {
    if ($value = $row->getSourceProperty('Tags')) {
      $row->setSourceProperty('Tags', explode(',', $value));
    }

    if ($value = $row->getSourceProperty('Image')) {
      $path = dirname($this->configuration['path']) . '/images/' . $value;

      $data = file_get_contents($path);
      $uri = file_build_uri($value);
      $file = file_save_data($data, $uri);

      $row->setSourceProperty('Image', $file);
    }
  }
}
