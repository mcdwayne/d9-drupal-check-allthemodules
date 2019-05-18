<?php

namespace Drupal\cmlstarter_demo\Plugin\migrate\source;

use Drupal\cmlstarter_demo\Utility\MigrationsSourceBase;

/**
 * Source for CSV.
 *
 * @MigrateSource(
 *   id = "s_tx_catalog"
 * )
 */
class StoreTxCatalog extends MigrationsSourceBase {
  public $src = 'tx-catalog';

  /**
   * {@inheritdoc}
   */
  public function getRows() {
    $rows = [];
    $this->files = FALSE;
    if ($source = $this->getContent($this->src)) {
      $this->files = $this->getFiles('cmlstarter-demo/catalog');
      foreach ($source as $key => $row) {
        $id = $row['uuid'];
        $rows[$id] = [
          'id' => $id,
          'vid' => 'catalog',
          'name' => $row['name'],
          'field_catalog_image' => $this->ensureFiles($row['field_catalog_image'], 'catalog'),
          'status' => 1,
          'weight' => $row['weight'],
        ];
        if (isset($row['parent']) && $row['parent']) {
          $rows[$id]['parent'] = $row['parent'];
        }
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
