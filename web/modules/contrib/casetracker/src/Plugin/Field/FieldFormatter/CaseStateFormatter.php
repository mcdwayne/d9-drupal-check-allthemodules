<?php

/**
 * @file
 * Contains Drupal\field_example\Plugin\Field\FieldFormatter\SimpleTextFormatter.
 */

namespace Drupal\casetracker\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'casetracker_status_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "casetracker_state_formatter",
 *   module = "casetracker",
 *   label = @Translation("Status Formatter"),
 *   field_types = {
 *     "casetracker_state"
 *   }
 * )
 */
class CaseStatusFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        // We create a render array to produce the desired markup,
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#attributes' => array(
          'style' => 'color: ' . $item->value,
        ),
        '#value' => print_r($item, 1)
      ,
      );
    }

    return $elements;
  }

}
