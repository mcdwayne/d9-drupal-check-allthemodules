<?php

/**
 * @file
 * Contains \Drupal\fidformatter\Plugin\field\formatter\FidFormatter.
 */

namespace Drupal\fidformatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'fid' formatter.
 *
 * @FieldFormatter(
 *   id = "fid",
 *   label = @Translation("File ID"),
 *   field_types = {
 *     "file",
 *     "image",
 *   }
 * )
 */
class FidFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items) {
    $elements = array();

    foreach ($items as $delta => $item) {
      $elements[$delta] = array('#markup' => $item->target_id);
    }

    return $elements;
  }

}
