<?php

namespace Drupal\parade\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Custom field formatter for Call to action links.
 *
 * It adds `target="_blank"` and `rel="noreferrer noopener"` to links.
 * The latter is needed because of a security flaw.
 *
 * @see https://mathiasbynens.github.io/rel-noopener/
 *
 * @FieldFormatter(
 *   id = "link_call_to_action",
 *   label = @Translation("Call to action"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class CallToActionFormatter extends LinkFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);

    foreach ($elements as &$element) {
      if (!empty($element['#options']['open_on_new_tab'])) {
        $element['#options']['attributes']['target'] = '_blank';
        $element['#options']['attributes']['rel'] = 'noreferrer noopener';
      }
    }

    return $elements;
  }

}
