<?php

namespace Drupal\private_content\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation for PrivateFormatter.
 *
 * @FieldFormatter(
 *   id = "private",
 *   label = @Translation("Private"),
 *   field_types = {
 *     "private"
 *   }
 * )
 */
class PrivateFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      if ($item->value) {
        $elements[$delta] = ['#markup' => $this->t('Private')];
      }
      else {
        $elements[$delta] = ['#markup' => $this->t('Public')];
      }
    }

    return $elements;
  }

}
