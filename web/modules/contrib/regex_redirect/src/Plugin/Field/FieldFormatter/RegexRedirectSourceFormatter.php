<?php

namespace Drupal\regex_redirect\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Implementation of the 'regex_redirect_source' formatter.
 *
 * @FieldFormatter(
 *   id = "regex_redirect_source",
 *   label = @Translation("Regex Redirect Source"),
 *   field_types = {
 *     "regex_redirect_source",
 *   }
 * )
 */
class RegexRedirectSourceFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $item->getUrl()->toString(),
      ];
    }

    return $elements;
  }

}
