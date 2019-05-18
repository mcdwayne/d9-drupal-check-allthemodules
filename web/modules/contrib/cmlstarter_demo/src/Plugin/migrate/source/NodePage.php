<?php

namespace Drupal\cmlstarter_demo\Plugin\migrate\source;

use Drupal\cmlstarter_demo\Utility\MigrationsSourceBase;

/**
 * Source for CSV.
 *
 * @MigrateSource(
 *   id = "s_page"
 * )
 */
class NodePage extends MigrationsSourceBase {
  public $src = 'node-page';

  /**
   * {@inheritdoc}
   */
  public function getRows() {
    $rows = [];
    if ($source = $this->getContent($this->src)) {
      foreach ($source as $key => $row) {
        $id = $row['uuid'];
        $row['body']['value'] = str_replace("\n", " ", $row['body']['value']);
        $rows[$id] = [
          'id' => $id,
          'uid' => 1,
          'type' => 'page',
          'status' => $row['status'],
          'sticky' => $row['sticky'],
          'promote' => $row['promote'],
          'created' => $row['created'],
          'changed' => $row['changed'],
          'title' => $row['title'],
          'path' => $row['path'],
          'body' => $row['body'],
        ];
      }
    }
    $this->debug = FALSE;
    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE) {
    $source = $this->getContent($this->src, TRUE);
    return count($source);
  }

}
