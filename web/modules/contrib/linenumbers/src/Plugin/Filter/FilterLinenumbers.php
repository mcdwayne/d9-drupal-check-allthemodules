<?php

namespace Drupal\linenumbers\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Define the filter for Linenumbers.
 *
 * @Filter(
 *   id = "filter_linenumbers",
 *   title = @Translation("Linenumbers Filter"),
 *   description = @Translation("Add line numbers to this text."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FilterLinenumbers extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $new_text = '<span class="linenumbers-filter">' . $text . '</span>';
    $result = new FilterProcessResult($new_text);

    $result->setAttachments([
      'library' => ['linenumbers/linenumbers'],
    ]);

    return $result;
  }

}
