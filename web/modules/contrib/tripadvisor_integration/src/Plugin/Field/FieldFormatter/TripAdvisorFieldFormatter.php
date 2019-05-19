<?php

namespace Drupal\tripadvisor_integration\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Field formatter for TripAdvisor ID field.
 *
 * @FieldFormatter(
 *   id = "tripadvisor_id_formatter",
 *   label = @Translation("TripAdvisor ID"),
 *   field_types = {"tripadvisor_integration_tripadvisor_id"}
 * )
 */
class TripAdvisorFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $tripadvisor_id = $item->getString();

      $tripadvisor_data = \Drupal::cache()->get('tripadvisor_integration:' . $tripadvisor_id . ':' . $langcode);

      if (empty($tripadvisor_data) || $tripadvisor_data->expire < \Drupal::time()->getRequestTime()) {
        $tripadvisor_data = tripadvisor_integration_fetch_content($tripadvisor_id, $langcode);
      }
      else {
        $tripadvisor_data = $tripadvisor_data->data;
      }

      $elements[$delta] = array(
        '#theme' => 'tripadvisor_integration',
        '#data' => $tripadvisor_data,
        '#tripadvisor_logo' => drupal_get_path('module', 'tripadvisor_integration') . '/images/tripadvisor.png',
      );
    }
    return $elements;
  }

}
