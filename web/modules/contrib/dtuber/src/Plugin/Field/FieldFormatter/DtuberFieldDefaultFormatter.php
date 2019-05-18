<?php

namespace Drupal\dtuber\Plugin\Field\FieldFormatter;

use \Drupal\Core\Field\FieldItemListInterface;
use \Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'dtuber_field_default_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "dtuber_field_default_formatter",
 *   module = "dtuber",
 *   label = @Translation("DTuber Field"),
 *   field_types = {
 *     "dtuber_field"
 *   }
 * )
 */
class DtuberFieldDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    foreach ($items as $delta => $item) {
      if ($item) {
        $options = array(
          'src' => 'https://www.youtube.com/v/' . $item->yt_videoid . '?version=3&autoplay=1',
          'value' => $item->fid,
          'vid' => $item->yt_videoid,
        );
        $elements[$delta] = array(
          '#theme' => 'dtuber_field_formatter',
          '#options' => $options,
        );
      }
    }
    return $elements;
  }

}
