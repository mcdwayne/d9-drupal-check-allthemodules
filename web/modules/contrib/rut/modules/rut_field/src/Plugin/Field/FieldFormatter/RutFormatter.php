<?php

/**
 * @file
 * Definition of Drupal\rut_field\Plugin\field\formatter\RutFormatter.
 */

namespace Drupal\rut_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Tifon\Rut\RutUtil;

/**
 * Plugin implementation of the 'rut_field_formatter_default' formatter.
 * @FieldFormatter(
 *   id = "rut_field_formatter_default",
 *   module = "rut_field",
 *   label = @Translation("Simple formatter of the Rut"),
 *   field_types = {
 *     "rut_field_rut"
 *   }
 * )
 */
class RutFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();
    foreach ($items as $delta => $item) {
      $output = RutUtil::formatterRut($item->rut, $item->dv);
      $elements[$delta] = array('#markup' => $output);
    }

    return $elements;

  }

}
