<?php

namespace Drupal\cmlmigrations\Plugin\migrate\source;

use Drupal\cmlmigrations\MigrationsSourceBase;
use Drupal\cmlmigrations\Utility\FindVariation;
use Drupal\cmlmigrations\Utility\FindImage;

/**
 * Source for CSV.
 *
 * @MigrateSource(
 *   id = "cml_commerce_product"
 * )
 */
class CommerceProduct extends MigrationsSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getRows() {
    $k = 0;
    $rows = [];
    $source = FALSE;
    $images = FALSE;
    $variations = FALSE;
    $source = \Drupal::service('cmlapi.parser_product')->parse();
    // $filepath = CurrentXmlPath::get('import');
    // $source = TovarParcer::getRows($filepath);
    if ($source && isset($source['data']) && !empty($source['data'])) {
      if (count($source['data']) > 400) {
        // Если вариаций много - грузим сразу все.
        $variations = FindVariation::getBy1cUuid();
        $images = FindImage::getBy1cImage();
      }
      foreach ($source['data'] as $id => $row) {
        if ($k++ < 100 || !$this->uipage) {
          $product = $row['product'];
          $offers = $row['offers'];
          $status = isset($product['status']) && $product['status'] == 'Удален' ? 0 : 1;
          $rows[$id] = [
            'uuid' => $id,
            'type' => 'product',
            'stores' => 1,
            'status' => $status,
            'title' => trim($product['Naimenovanie']),
            'catalog' => $product['Gruppy'][0],
            'body' => [
              'value' => $product['Opisanie'],
              'format' => 'basic_html',
            ],
          ];
          $this->hasVariations($rows, $variations, $id);
          $this->hasImage($rows, $images, $product, $id);
        }
      }
    }
    $this->debug = FALSE;
    return $rows;
  }

  /**
   * HasVariations.
   */
  public function hasVariations(&$rows, $variations, $id) {
    $result = FALSE;
    if (!$variations) {
      // Ищем вариации текущего товара.
      $variations = FindVariation::getBy1cUuid($id, FALSE);
    }
    if (isset($variations[$id])) {
      $result = $variations[$id];
      $rows[$id]['variations'] = $result;
    }
    return $result;
  }

  /**
   * HasImage.
   */
  public static function hasImage(&$rows, $images, $product, $id, $field = 'Kartinka') {
    $result = FALSE;
    if (isset($product[$field])) {
      $image = $product[$field];
      if (is_array($image)) {
        $image = reset($image);
      }

      if (!$images) {
        // Fing image.
        $images = FindImage::getBy1cImage($image, FALSE);
      }
      if (isset($images[$image])) {
        $result = $images[$image];
        $rows[$id]['field_image'] = [
          'target_id' => $result,
        ];
      }
    }
    return $result;
  }

}
