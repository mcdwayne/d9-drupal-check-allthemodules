<?php

namespace Drupal\table_altrow\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Insert even and odd classes for tables via input filters to allow for proper
 * zebra-style striping.
 *
 * @Filter(
 *   id = "table_altrow",
 *   title = @Translation("Add even and odd classes to table rows."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class TableAltrow extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    // The actual filtering is performed here. The supplied text should be
    // returned, once any necessary substitutions have taken place.
    // First, we have to parse the variable.
    $matches = [];
    $offset = 0;
    // Find a tbody.
    while (preg_match('!(<tbody ?.*>)!i', $text, $matches, PREG_OFFSET_CAPTURE, $offset)) {
      $offset = $matches[0][1];
      $count = 1;
      // While the tbody is still open.
      while (preg_match('!(<tr( ?.*)>)|(</tbody>)!i', $text, $matches, PREG_OFFSET_CAPTURE, $offset)) {
        // +1 so we don't match the same string.
        $offset = $matches[0][1] + 1;

        // Don't process tr's until we find a tbody.
        if ($matches[0][0] == '</tbody>') {
          break;
        }

        // Don't replace existing classes. Perhaps this should append a class instead?
        if (!strstr($matches[2][0], 'class=')) {
          if (($count % 2) == 0) {
            $new_tag = '<tr class="even"' . $matches[2][0] . '>';
            $text = table_altrow_str_replace_count($matches[0][0], $new_tag, $text, $offset - 1, 1);
          }
          else {
            $new_tag = '<tr class="odd"' . $matches[2][0] . '>';
            $text = table_altrow_str_replace_count($matches[0][0], $new_tag, $text, $offset - 1, 1);
          }
        }
        $count++;
      }
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    if ($long) {
      return $this->t('Tables will be rendered with different styles for even and odd rows if supported.');
    }
  }

}