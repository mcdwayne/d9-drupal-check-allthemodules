<?php

namespace Drupal\rut_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\rut\Rut;

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
    $elements = [];
    foreach ($items as $delta => $item) {
      $output = Rut::formatterRut($item->rut, $item->dv);
      $elements[$delta] = ['#markup' => $output];
    }

    return $elements;

  }

}
