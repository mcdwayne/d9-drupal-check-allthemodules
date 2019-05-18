<?php

namespace Drupal\cmlmigrations\Utility;

/**
 * FindVariation helper.
 */
class FindVariation {

  /**
   * Find variations.
   */
  public static function getBy1cUuid($id1c = FALSE, $all = TRUE) {
    $variations = [];

    if ($id1c || $all) {
      $query = \Drupal::database()->select('commerce_product_variation_field_data', 'variations');
      $query->fields('variations', [
        'variation_id',
        'sku',
        'product_id',
        'product_uuid',
      ]);
      if ($id1c) {
        $query->condition('product_uuid', $id1c);
      }
      else {
        $query->isNotNull('product_uuid');
      }
      $res = $query->execute();

      if ($res) {
        foreach ($res as $key => $row) {
          $id1c = $row->product_uuid;
          $variations[$id1c][] = [
            'src' => $row->sku,
            'target_id' => $row->variation_id,
          ];
        }
      }
    }
    return $variations;
  }

}
