<?php

namespace Drupal\hash_tag_make\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Adds a filter to create Hash Tag links.
 *
 * @Filter(
 *   id = "filter_hash_tag_make",
 *   title = @Translation("Hash Tag Make Filter"),
 *   description = @Translation("Turn strings beginning with '#' into links with search url href value."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FilterHashTagMake extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {

    // Get form config.
    $config = \Drupal::config('hash_tag_make.hash_tag_make_settings');

    // Set search pattern.
    $searchPattern = $config->get('search_pattern') ?: '/search/node?keys=';

    // Set filter language.
    $filterLanguage = $config->get('script');

    // Instantiate variable for preg_replace as arrays.
    $replacePattern = [];

    foreach ($filterLanguage as $language) {

      // Ensure there is a language value.
      if ($language != '') {

        // Search for any word that starts with '#' by language.
        $replacePattern[] = '~(?<!\p{' . $language . '})#(\p{' . $language . '}+)~u';

      }

    }

    // Create our replacement.
    $replacement = '<a href="' . $searchPattern . '%23$1" class="hashtag">#$1</a>';

    // Execute replacement.
    $replaceText = preg_replace($replacePattern, $replacement, $text);

    return new FilterProcessResult($replaceText);
  }

}
