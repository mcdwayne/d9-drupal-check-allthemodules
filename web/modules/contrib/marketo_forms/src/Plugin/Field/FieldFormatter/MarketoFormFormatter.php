<?php

/**
 * @file
 * Contains Drupal\marketo_forms\Plugin\Field\FieldFormatter\MarketoFormFormatter.
 */

namespace Drupal\marketo_forms\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\marketo_forms\MarketoFormsCore;

/**
 * Plugin implementation of the 'field_marketo_form_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "field_marketo_form_formatter",
 *   module = "marketo_forms",
 *   label = @Translation("Display Marketo form"),
 *   field_types = {
 *     "field_marketo_form"
 *   }
 * )
 */
class MarketoFormFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $config = \Drupal::config('marketo_forms.settings');
    $host = $config->get('marketo_host_key');
    $api_key = $config->get('marketo_api_key');

    foreach ($items as $delta => $item) {
        $elements[$delta] = [
          '#theme'     => 'marketo_form',
          '#host' => $host,
          '#api_key' => $api_key,
          '#form_id' => $item->form_id,
          '#locale'    => $langcode,
        ];
    }

    return $elements;
  }

}
