<?php

namespace Drupal\twig_extender\Plugin\TwigPlugin;

use Drupal\twig_extender\Plugin\Twig\TwigPluginBase;
use Drupal\Component\Utility\Unicode;

/**
 * Example plugin for truncate.
 *
 * @TwigPlugin(
 *   id = "twig_extender_truncate",
 *   label = @Translation("Truncate string"),
 *   type = "filter",
 *   name = "truncate",
 *   function = "trunc"
 * )
 */
class Truncate extends TwigPluginBase {

  /**
   * Implement truncate filter.
   */
  public function trunc($string,
                           $maxLength,
                           $wordsafe = FALSE,
                           $addEllipsis = FALSE,
                           $minWordsafeLength = 1) {
    return Unicode::truncate($string, $maxLength, $wordsafe, $addEllipsis, $minWordsafeLength);
  }

}
