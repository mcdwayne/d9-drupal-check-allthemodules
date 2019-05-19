<?php

namespace Drupal\sieroty\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Adds non breaking space after conjunction.
 *
 * @Filter(
 *   id = "sieroty_text_filter",
 *   title = @Translation("Sieroty Text Filter"),
 *   description = @Translation("Adds non breaking space after conjunction."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *   weight = -10
 * )
 */
class SierotyFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {

    if ($langcode == 'pl' || $langcode = 'cz') {
      $text = preg_replace("/(?<= )((a|i|o|u|w|z|A|I|O|U|W|Z|H|B|C|N|F|PS|K|V|Y) )/", "$2&nbsp;", $text);
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('Adds non breaking space after conjunction.');
  }

}
