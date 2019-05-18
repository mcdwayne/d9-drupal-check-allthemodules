<?php

namespace Drupal\cmlstarter_demo\Plugin\migrate\source;

use Drupal\cmlstarter_demo\Utility\MigrationsSourceBase;

/**
 * Source for CSV.
 *
 * @MigrateSource(
 *   id = "s_tx_brand"
 * )
 */
class StoreTxBrand extends MigrationsSourceBase {
  public $src = 'tx-brand';

  /**
   * {@inheritdoc}
   */
  public function getRows() {
    $rows = [];
    $this->files = FALSE;
    if ($source = $this->getContent($this->src)) {
      $this->files = $this->getFiles('cmlstarter-demo/brand');
      foreach ($source as $key => $row) {
        $id = $row['uuid'];
        $img = $row['field_brand_image']['filename'];
        $rows[$id] = [
          'id' => $id,
          'vid' => 'brand',
          'name' => $row['name'],
          'weight' => $row['weight'],
          'link' => $row['field_brand_link'],
          'short' => $row['field_brand_short'],
          'image' => $this->ensureFiles($img, 'brand'),
          'status' => 1,
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
