<?php

/**
 * @file
 * Contains \Drupal\loremipsum\Controller\LoremIpsumController
 */

namespace Drupal\loremipsum\Controller;

use Drupal\Core\Url;
// Change following https://www.drupal.org/node/2457593
use Drupal\Component\Utility\SafeMarkup;

/**
 * Controller routines for Lorem ipsum pages.
 */
class LoremIpsumController {

  /**
   * Constructs Lorem ipsum text with arguments.
   * This callback is mapped to the path
   * 'loremipsum/generate/{lorem}/{ipsum}'.
   * 
   * @param string $lorem
   *   First part of your text block.
   * @param string $ipsum
   *   Second part of your text block.
   */
  public function generate($paragraphs, $phrases) {
    // Default settings
    $config = \Drupal::config('loremipsum.settings');
    // Page title and source text.
    $page_title = $config->get('loremipsum.page_title');
    $source_text = $config->get('loremipsum.source_text');

    /* Repertory strategy.
     * TODO: different strategies:
     * - break on periods (like below)
     * - random words
     * - standard lorem ipsum with custom words thrown in
     * - begin with "Lorem Ipsum" etc.
     */
    $repertory = explode(PHP_EOL, str_replace(array("\r\n", "\n\r", "\r", "\n"), PHP_EOL, $source_text));

    $element['#source_text'] = array();

    // Generate X paragraphs with up to Y phrases each
    for ($i = 1; $i <= $paragraphs; $i++) {
      $this_paragraph = '';
      // When we say "up to Y phrases each", we can't mean "from 1 to Y".
      // So we go from halfway up.
      $random_phrases = mt_rand(round($phrases/2), $phrases);
      // Also don't repeat the last phrase.
      $last_number = 0;
      $next_number = 0;
      for ($j = 1; $j <= $random_phrases; $j++) {
        do {
          $next_number = floor(mt_rand(0, count($repertory)-1));
        } while ($next_number === $last_number && count($repertory) > 1);
        $this_paragraph .= $repertory[$next_number] . ' ';
        $last_number = $next_number;
      }
      $element['#source_text'][] = SafeMarkup::checkPlain($this_paragraph);
    }
    $element['#title'] = SafeMarkup::checkPlain($page_title);

    // Theme function
    $element['#theme'] = 'loremipsum';

    return $element;
  }

}
