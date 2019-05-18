<?php

namespace Drupal\cmlstarter_demo\Plugin\migrate\source;

use Drupal\cmlstarter_demo\Utility\MigrationsSourceBase;

/**
 * Source for CSV.
 *
 * @MigrateSource(
 *   id = "s_product_variation"
 * )
 */
class StoreProductVariation extends MigrationsSourceBase {
  public $src = 'product';

  /**
   * {@inheritdoc}
   */
  public function getRows() {
    $rows = [];
    if ($source = $this->getContent($this->src)) {
      foreach ($source as $product) {
        $pid = $product['uuid'];
        $title = $product['title'];
        if (!empty($product['variations'])) {
          foreach ($product['variations'] as $vid => $variation) {
            $id = "{$pid}:{$vid}";
            $rows[$id] = [
              'id' => $id,
              'type' => 'variation',
              'title' => $title,
              'sku' => $id,
              'product_uuid' => $pid,
              'price' => [
                'number' => (int) filter_var($variation['price'], FILTER_SANITIZE_NUMBER_INT),
                'currency_code' => $variation['currency'],
              ],
              'field_oldprice' => $variation['field_oldprice'],
            ];
          }
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
    $rows = [];
    if ($source = $this->getContent($this->src)) {
      foreach ($source as $product) {
        $pid = $product['uuid'];
        if (!empty($product['variations'])) {
          foreach ($product['variations'] as $vid => $variation) {
            $id = "{$pid}:{$vid}";
            $rows[$id] = [
              'id' => $id,
              'price' => [
                'number' => $variation['price'],
                'currency_code' => $variation['currency'],
              ],
            ];
          }
        }
      }
    }
    return count($rows);
  }

}
