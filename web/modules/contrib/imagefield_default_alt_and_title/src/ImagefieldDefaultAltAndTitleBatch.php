<?php

namespace Drupal\imagefield_default_alt_and_title;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\commerce_product\Entity\Product;

class ImagefieldDefaultAltAndTitleBatch {

  /**
   * Batch procased.
   */
  static function addedData($data, $type, &$context) {
    $results = array();
    $config = \Drupal::config('system.site')
                     ->get('imagefield_default_alt_and_title_default_values');
    $limit = 1;
    if (empty($context['sandbox']['progress'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($data);
    }
    if (empty($context['sandbox']['items'])) {
      $context['sandbox']['items'] = $data;
    }
    $counter = 0;
    if (!empty($context['sandbox']['items'])) {
      if ($context['sandbox']['progress'] != 0) {
        array_splice($context['sandbox']['items'], 0, $limit);
      }
      foreach ($context['sandbox']['items'] as $item) {
        if ($counter != $limit) {
          switch ($type) {
            case 'node_entity':
              $node = Node::load($item);
              $node_fields = $node->getFieldDefinitions();
              $node = ImagefieldDefaultAltAndTitleBatch::changeValue($node_fields, $node, $config);
              $results[] = $node->save();
              break;
            case 'taxonomy_vocabulary':
              $term = Term::load($item);
              $term_fields = $term->getFieldDefinitions();
              $term = ImagefieldDefaultAltAndTitleBatch::changeValue($term_fields, $term, $config);
              $results[] = $term->save();
              break;
            case 'commerce_product_type':
              $moduleHandler = \Drupal::service('module_handler');
              if ($moduleHandler->moduleExists('commerce_product')) {
                $product = Product::load($item);
                $product_fields = $product->getFieldDefinitions();
                $product = ImagefieldDefaultAltAndTitleBatch::changeValue($product_fields, $product, $config);
                $results[] = $product->save();
              }
              break;
          }

          $counter++;
          $context['sandbox']['progress']++;
          $context['message'] = t('Now processing node %progress of %count', array(
            '%progress' => $context['sandbox']['progress'],
            '%count' => $context['sandbox']['max'],
          ));
          $context['results']['processed'] = $context['sandbox']['progress'];
        }
      }
    }

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Finished callback for batch.
   */
  static function addedDataFinishedCallback($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()
                        ->formatPlural($results['processed'], 'One post processed.', '@count posts processed.');
    }
    else {
      $message = t('Finished with an error.');
    }
    \Drupal::messenger()->addMessage($message);
  }

  /**
   * Change value for image field.
   */
  static function changeValue($fields = [], $data, $config) {
    $result = [];
    if (!empty($fields) && !empty($data) && !empty($config)) {
      foreach ($fields as $key => $value) {
        $change_enable = FALSE;
        if ($value->getType() == 'image') {
          if (!empty($data->get($key)->getValue())) {
            $field_value = $data->get($key)->getValue();
            foreach ($field_value as $fv_key => $fv_data) {
              if (empty($field_value[$fv_key]['alt'])) {
                $field_value[$fv_key]['alt'] = $config['alt'];
                $change_enable = TRUE;
              }
              if (empty($field_value[$fv_key]['title'])) {
                $field_value[$fv_key]['title'] = $config['title'];
                $change_enable = TRUE;
              }
            }
            if ($change_enable) {
              $data->set($key, $field_value);
            }
          }
        }
      }
      $result = $data;
    }

    return $result;
  }
}
