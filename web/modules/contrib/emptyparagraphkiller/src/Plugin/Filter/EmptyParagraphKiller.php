<?php

/**
 * @file
 * Contains \Drupal\emptyparagraphkiller\Plugin\Filter\EmptyParagraphKiller.
 */

namespace Drupal\emptyparagraphkiller\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to remove empty paragraphs.
 *
 * @Filter(
 *   id = "emptyparagraphkiller",
 *   title = @Translation("Remove empty paragraphs"),
 *   description = "When entering more than one carriage return, only the first will be honored.",
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = 10
 * )
 */
class EmptyParagraphKiller extends FilterBase {

  /**
   * Performs the filter processing.
   */
  public function process($text, $langcode) {
    return new FilterProcessResult(preg_replace('/<p[^>]*>(&nbsp;|\s)*<\/p>/ui', '', $text));
  }

}
