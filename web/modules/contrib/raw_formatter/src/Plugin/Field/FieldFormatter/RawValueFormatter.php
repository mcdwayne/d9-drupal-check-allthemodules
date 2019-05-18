<?php

/**
 * @file
 * Contains RawValueFormatter Class.
 */

namespace Drupal\raw_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'raw_value' formatter.
 *
 * @FieldFormatter(
 *   id = "raw",
 *   label = @Translation("Raw Value"),
 *   field_types = {
 *    "metatag",
 *   }
 * )
 */
class RawValueFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity = $items->getEntity();

    $elements = array();
    foreach ($items as $delta => $item) {
      $value = $item->getValue();

      // Replace the tokens with their respective values.
      $data = array();
      foreach(unserialize($value['value']) as $key => $key_value) {
        $data[$key] = \Drupal::token()->replace($key_value, [$entity->getEntityTypeId() => $entity]);
        $data[$key] = preg_replace ('/<[^>]*>/', '', $data[$key]);
      }

      $elements[$delta] = [
        '#theme' => 'raw_formatter',
        '#raw_value' => json_encode($data),
      ];
    }
    return $elements;
  }

}
