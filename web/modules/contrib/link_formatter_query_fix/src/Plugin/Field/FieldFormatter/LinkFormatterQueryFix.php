<?php

namespace Drupal\link_formatter_query_fix\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\link\Plugin\Field\FieldFormatter\LinkFormatter;

/**
 * Plugin implementation of the 'link__formatter_query_fix' formatter.
 *
 * Remove options from element item['#options'], to prevent duplication on
 * merge with item['#url']->getOptions().
 * This fix for duplication of parameters issue 2885351.
 *
 * @see \Drupal\Core\Render\Element\Link::preRenderLink().
 * @see https://www.drupal.org/node/2885351
 *
 * @FieldFormatter(
 *   id = "link__formatter_query_fix",
 *   label = @Translation("Link (query duplication fix)"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */
class LinkFormatterQueryFix extends LinkFormatter {
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = parent::viewElements($items, $langcode);

    foreach ($element as $delta => $item) {
      if (isset($element[$delta]['#options'])) {
        $element[$delta]['#options'] = [];
      }
    }

    return $element;
  }
}
