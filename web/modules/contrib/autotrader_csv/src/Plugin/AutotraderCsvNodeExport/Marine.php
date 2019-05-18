<?php

namespace Drupal\autotrader_csv\Plugin\AutotraderCsvNodeExport;

use Drupal\autotrader_csv\Plugin\AutotraderCsvNodeExportBase;
use Drupal\autotrader_csv\Plugin\AutotraderCsvNodeExportInterface;
use Drupal\node\NodeInterface;

/**
 * Provides a way to process product nodes.
 *
 * @AutotraderCsvNodeExport(
 *   id = "marine",
 *   admin_label = @Translation("AutoTrader CSV Export Product Nodes")
 * )
 */
class Marine extends AutotraderCsvNodeExportBase implements AutotraderCsvNodeExportInterface {

  /**
   * {@inheritdoc}
   */
  public function setNode(NodeInterface $node) {
    parent::setNode($node);

    $default_field_values = [
      'field_name' => NULL,
      'delta_start' => 0,
      'value' => AutotraderCsvNodeExportBase::DEFAULT_FIELD_VALUE,
      'include_multi' => FALSE,
      'file_id' => NULL,
      'value_key' => 'value',
      'is_term' => FALSE,
      'is_file' => FALSE,
      'is_timestamp' => FALSE,
    ];

    foreach ($this->csvColumns as $column) {
      switch ($column) {
        case "year":
          $this->fields[$column] = array_merge($default_field_values, [
            'field_name' => "field_year",
          ]);
          break;

        case "make":
          $this->fields[$column] = array_merge($default_field_values, [
            'field_name' => "field_make",
          ]);
          break;

        case "model":
          $this->fields[$column] = array_merge($default_field_values, [
            'field_name' => "field_model",
          ]);
          break;

        case "trim":
          $this->fields[$column] = array_merge($default_field_values, [
            'field_name' => "field_trim",
          ]);
          break;

        case "sku":
          $this->fields[$column] = array_merge($default_field_values, [
            'field_name' => "field_sku",
          ]);
          break;

        case "vin":
          $this->fields[$column] = array_merge($default_field_values, [
            'field_name' => "field_vin",
          ]);
          break;

        case "category_id":
          $this->fields[$column] = array_merge($default_field_values, [
            'field_name' => "field_autotrader_category",
            'value_key' => 'target_id',
            'is_term' => TRUE,
            'use_field_on_term' => 'field_autotrader_category_id',
          ]);
          break;

        case "last_modified":
          $this->fields[$column] = array_merge($default_field_values, [
            'field_name' => "changed",
            'is_timestamp' => TRUE,
          ]);
          break;

        case "photo":
          $this->fields[$column] = array_merge($default_field_values, [
            'field_name' => "field_images",
            'value_key' => 'target_id',
            'is_file' => TRUE,
          ]);
          break;

        case "photo_last_modified":
          if (!empty($node->get("field_images")->getValue())) {
            $photo_id = $node->get("field_images")->getValue()[0]['target_id'];
            $file_storage = $this->entityTypeManager->getStorage('file');
            $photo = $file_storage->load($photo_id);
            $this->fields[$column] = array_merge($default_field_values, [
              'field_name' => 'field_images',
              'value' => date('m/d/Y', $photo->get("changed")->getValue()[0]['value']),
            ]);
          }
          break;

        case "additional_photos":
          $this->fields[$column] = array_merge($default_field_values, [
            'field_name' => "field_images",
            'delta_start' => 1,
            'include_multi' => TRUE,
            'value_key' => 'target_id',
            'is_file' => TRUE,
          ]);
          break;

        case "additional_photos_last_modified":
          $this->fields[$column] = array_merge($default_field_values, [
            'field_name' => "field_images",
            'delta_start' => 1,
            'include_multi' => TRUE,
            'value_key' => 'target_id',
            'is_timestamp' => TRUE,
          ]);
          break;
      }
    }
  }

}
