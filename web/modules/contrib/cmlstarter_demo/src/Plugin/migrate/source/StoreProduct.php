<?php

namespace Drupal\cmlstarter_demo\Plugin\migrate\source;

use Drupal\cmlstarter_demo\Utility\MigrationsSourceBase;

/**
 * Source for CSV.
 *
 * @MigrateSource(
 *   id = "s_product"
 * )
 */
class StoreProduct extends MigrationsSourceBase {
  public $src = 'product';

  /**
   * {@inheritdoc}
   */
  public function getRows() {
    $rows = [];
    $k = 0;
    $this->files = FALSE;
    $this->product_options = FALSE;
    if ($source = $this->getContent($this->src)) {
      foreach ($source as $key => $row) {
        $id = $row['uuid'];
        $this->product_options = $this->getTerms('product_options');
        $this->files = $this->getFiles('cmlstarter-demo/product');
        $variations = [];
        if (!empty($row['variations'])) {
          foreach ($row['variations'] as $k => $v) {
            $variations["{$id}:{$k}"] = "{$id}:{$k}";
          }
        }
        if ($k++ < 100 || !$this->uipage) {
          $rows[$id] = [
            'id' => $id,
            'uuid' => "{$id}-0000-0000-0000-000000000000",
            'type' => $row['type'],
            'stores' => $row['stores'],
            'status' => $row['status'],
            'created' => $row['created'],
            'changed' => $row['changed'],
            'title' => $row['title'],
            'field_title' => $row['field_title'],
            'field_catalog' => $row['field_catalog'],
            'field_tx_brand' => $row['field_tx_brand'],
            'field_short' => $row['field_short'],
            'variations' => $row['variations'] ,
            'field_image' => $this->ensureFiles($row['field_image'], 'product'),
            'field_gallery' => $this->ensureFiles($row['field_gallery'], 'product'),
            'field_attach' => $this->ensureFiles($row['field_attach'], 'product'),
            'field_paragraph' => $this->ensureParagraph($row['field_paragraph']),
            'field_tx_options' => $this->hasProductOptions($row['field_tx_options']),
            'field_rf_product' => $this->hasProduct($row['field_rf_product']),
            'body' => $row['body'],
          ];
          $rows[$id]['variations'] = array_shift($variations);
        }
      }
    }
    $this->debug = TRUE;
    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE) {
    $source = $this->getContent($this->src, TRUE);
    return count($source);
  }

  /**
   * Ensures the existence of a paragraph.
   */
  public function ensureParagraph($field_paragraph) {
    $paragraph_storage = \Drupal::entityManager()->getStorage('paragraph');
    foreach ($field_paragraph as $key => $val) {
      if (!empty($val[0]) && !empty($val[1])) {
        $paragraph_arr = $paragraph_storage->loadByProperties([
          'field_product_param_param' => !empty($val[0]) ? $val[0] : '',
          'field_product_param_value' => !empty($val[1]) ? $val[1] : '',
        ]);
      }
      elseif (!empty($val[0])) {
        $paragraph_arr = $paragraph_storage->loadByProperties([
          'field_product_param_param' => !empty($val[0]) ? $val[0] : '',
        ]);
      }

      $paragraph = reset($paragraph_arr);
      if (!$paragraph) {
        $paragraph = $paragraph_storage->create([
          'type' => 'product_param',
          'field_product_param_param' => !empty($val[0]) ? $val[0] : '',
          'field_product_param_value' => !empty($val[1]) ? $val[1] : '',
        ]);
        $paragraph->save();
      }
      if (is_object($paragraph)) {
        $id = $paragraph->id();
        $result[$id] = [
          'target_id' => $paragraph->id(),
          'target_revision_id' => $paragraph->getRevisionId(),
        ];
      }
    }
    return $result;
  }

}
