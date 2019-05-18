<?php

namespace Drupal\chinese_identity_card\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;


/**
 * @FieldFormatter(
 *   id = "chinese_identity_card",
 *   label = @Translation("Chinese identity card"),
 *   field_types = {
 *      "chinese_identity_card"
 *   }
 * )
 */
class ChineseIdentityCardFormatter extends FormatterBase {

  /**
   * @inheritdoc
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = array(
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $item->value,
      );
    }

    return $elements;
  }
}