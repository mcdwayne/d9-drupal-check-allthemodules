<?php

namespace Drupal\spammaster\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides 'Total Threats Count' Filter Block.
 *
 * @Filter(
 *   id = "filter_total_block_count",
 *   title = @Translation("Spam Master Total Threats Count Filter"),
 *   description = @Translation("Inserts total count filter inside content using [spammaster_total_threats_count]"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class SpamMasterTotalCountFilterBlock extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {

    // Get Total Threats Count from module settings.
    $spammaster_settings = \Drupal::config('spammaster.settings');
    $spammaster_total_threats_count = number_format($spammaster_settings->get('spammaster.license_protection'));

    $replace = $spammaster_total_threats_count;
    $new_text = str_replace('[spammaster_total_threats_count]', $replace, $text);

    return new FilterProcessResult($new_text);

  }

}
