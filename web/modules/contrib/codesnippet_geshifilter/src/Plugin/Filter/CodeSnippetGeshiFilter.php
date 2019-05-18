<?php

namespace Drupal\codesnippet_geshifilter\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Drupal 8 filter to convert CodeSnippet markup to GeSHi markup.
 *
 * @Filter(
 *   id = "codesnippet_geshifilter",
 *   title = @Translation("CodeSnippet To GeSHi Filter"),
 *   description = @Translation("Converts CodeSnippet markup to GeSHi markup."),
 *   type = \Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class CodeSnippetGeshiFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // Create dom document.
    $dom = Html::load($text);
    $remove = [];

    // Remove '<pre>' wrappers around [geshifilter] tokens.
    $pre = $dom->getElementsByTagName('pre');
    if ($pre) {
      foreach ($pre as $node) {
        // Whether geshifilter added its tokens.
        if (strpos($node->nodeValue, '[geshifilter') !== FALSE) {
          while ($node->hasChildNodes()) {
            $child = $node->removeChild($node->firstChild);
            // Whether it is a text node.
            if ($child->nodeType == 3) {
              $processed = preg_replace_callback(
                '/(\[geshifilter\-[^\]]*class\s*=\s*")([^"]*)/i',
                [
                  $this,
                  'processCallback',
                ],
                $child->nodeValue
              );
              $node->parentNode->insertBefore($dom->createTextNode($processed), $node);
            }
            else {
              $node->parentNode->insertBefore($child, $node);
            }
          }
          // Remark for removal.
          $remove[] = $node;
        }
      }
      // We have to do a second loop to remove the items, as otherwise the above
      // loop won't work correctly.
      foreach ($remove as $node) {
        // Remove the remaining (empty) <pre> tags.
        $node->parentNode->removeChild($node);
      }
    }

    // We apply our callback again in order to catch inline code not sur-
    // rounded by <pre> tags.
    return new FilterProcessResult(
      preg_replace_callback(
        '/(\[geshifilter\-[^\]]*class\s*=\s*")([^"]*)/i',
        [
          $this,
          'processCallback',
        ],
        Html::serialize($dom)
      )
    );
  }

  /**
   * Preg replace callback.
   *
   * Replaces language-[...] CSS class attribute values with
   * language attribute.
   *
   * @param array $matches
   *   Preg_replace matches array.
   *
   * @return string
   *   Transformed replacement string.
   */
  public function processCallback(array $matches) {
    $language = '';
    $classes = explode(' ', $matches[2]);
    foreach ($classes as $value) {
      if (strpos($value, 'language-') === 0) {
        $language = substr($value, 9, strlen($value) - 9);
        if (!empty($language)) {
          $classes = array_diff($classes, [$value]);
          break;
        }
      }
    }

    // @TODO: 'Translate' CodeSnippet language identifiers to GeSHi language identifiers.

    // Compose replacement.
    $processed = str_replace(
      'class="" ',
      '',
      $matches[1] .
      implode(' ', $classes) .
      ((!empty($language)) ? ('" language="' . $language) : '')
    );
    return $processed;
  }

}
