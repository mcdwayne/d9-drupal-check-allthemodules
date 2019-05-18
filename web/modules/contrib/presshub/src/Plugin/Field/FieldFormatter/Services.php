<?php

namespace Drupal\presshub\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'field_presshub_services_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "field_presshub_services_formatter",
 *   module = "presshub",
 *   label = @Translation("Services"),
 *   field_types = {
 *     "field_presshub_services"
 *   }
 * )
 */
class Services extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => !empty($item->service_name) ? $item->service_name : $this->t('Please configure Presshub module.'),
      ];
    }

    return $elements;
  }

}
