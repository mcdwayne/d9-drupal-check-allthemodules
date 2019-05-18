<?php

/**
 * @file
 * Contains \Drupal\multicolumn\Plugin\Filter\Multicolumn.
 */

namespace Drupal\multicolumn\Plugin\Filter;

use Drupal\filter\Annotation\Filter;
use Drupal\core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to split a list into multiple columns.
 *
 * @Filter(
 *   id = "filter_multicolumn",
 *   title = @Translation("Make a multi-column list."),
 *   descriptions = @Translation("Replace lines between %open and %close tags
 *   with several lists, arranged side by side.", arguments = {
 *     "%open" = "<multicolumn>",
 *     "%close" = "</multicolumn>"
 *   }),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE
 * )
 */
class Multicolumn extends FilterBase {

  /**
   * {@inheritdoc}
   *
   * Change the tags <multicolumn cols="3"> and </multicolumn> to
   * [multicolumn cols="3"] and [/multicolumn] so they will not be stripped by
   * the HTML filter (if it is enabled).
   */
  public function prepare($text, $langcode) {
    return preg_replace('@<(\s*/?multicolumn(\s+\w+\s*=\s*"\s*\w+\s*")*\s*)>@i',
      '[\1]', $text);
  }

  /**
   * {@inheritdoc}
   *
   * Generate a multi-column list from
   * [multicolumn cols="3"]
   * // lines ...
   * [/multicolumn]
   */
  public function process($text, $langcode) {
    // Split into chunks at the opening and closing "tags".
    $tagpattern = '@(\[\s*/?multicolumn(?:\s+\w+\s*=\s*"\w+")*\s*\])@i';
    $tagopen = '@^\[\s*multicolumn(\s+\w+\s*=\s*"\w+")*\s*\]@i';
    $tagclose = '@^\[\s*/multicolumn\s*\]@i';
    $chunks = preg_split($tagpattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    // Note: PHP ensures the array consists of alternating delimiters and
    // literals and begins and ends with a literal (inserting NULL as required).
    if (count($chunks) == 1) {
      return new FilterProcessResult($text);
    }

    // In the foreach loop, $columns is set to a positive integer when an
    // opening tag is found, then reset to 0 when a closing tag is found.
    $columns = 0;
    foreach ($chunks as $i => $chunk) {
      if (preg_match($tagopen, $chunk)) {
        // I love this combination of preg_match_all() and array_combine()!
        // It turns 'attr="val"' into ['attr' => 'val'].
        // Since the matches are of the form \w+, I do not think I have to
        // sanitize them (e.g., with check_plain()).
        $attributes
          = preg_match_all('/(\w+)\s*=\s*"(\w+)\s*"/', $chunk, $matches)
          ? array_combine($matches[1], $matches[2]) : [];
        $columns = (isset($attributes['cols'])) ? intval($attributes['cols']) : 1;
        unset($attributes['cols']);
        // Mark the start of a multicolumn section.
        $comment = ['#theme' => 'multicolumn_comment', '#text' => t('multicolumn start')];
        $chunks[$i] = render($comment);
      }
      elseif (preg_match($tagclose, $chunk)) {
        $columns = 0;
        // Mark the end of a multicolumn section.
        $comment = ['#theme' => 'multicolumn_comment', '#text' => t('multicolumn end')];
        $chunks[$i] = render($comment);
      }
      elseif ($columns > 0) {
        // $columns and $attributes were defined while processing the opening
        // tag. Start building the render element to pass to the theme function.
        $variables = ['#theme' => 'multicolumn_list', '#columns' => $columns];
        if (isset($attributes['type'])) {
          $variables['#type'] = $attributes['type'];
          unset($attributes['type']);
        }
        else {
          $variables['#type'] = 'ul';
        }
        // We removed 'cols' and 'type' from $attributes.  Pass whatever is left
        // to the theme function.
        $variables['#attributes'] = $attributes;
        $rows = explode("\n", $chunk);
        // If (as usual) there are newlines after the opening tag and before the
        // closing tag, remove the blank entries.
        if ($item = array_pop($rows)) {
          $rows[] = $item;
        }
        if ($item = array_shift($rows)) {
          array_unshift($rows, $item);
        }
        // Each entry in $cells will be the render element for one column.
        $cells = [];
        for ($j = 0; $columns > 0; --$columns) {
          // For ordered lists, do not start each at 1.
          // The start attribute is deprecated in HTML 4.01, supported in 5:
          // http://www.w3schools.com/html5/tag_ol.asp .
          if ($variables['#type'] == 'ol') {
            $variables['#attributes']['start'] = $j + 1;
          }
          // Calculate how many rows to use for this column.
          $delta = ceil((count($rows) - $j) / $columns);
          $cells[] = $variables
            + ['#items' => array_slice($rows, $j, $delta)];
          $j += $delta;
        }
        $row = ['#theme' => 'multicolumn_row', '#items' => $cells];
        $chunks[$i] = render($row);
        // Done processing until the next opening tag.
        $columns = 0;
      }
    }

    $text = implode("\n", $chunks);
    return new FilterProcessResult(trim($text));
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $output = t('Generate a multi-column list with the %tag tag.',
      ['%tag' => '<multicolumn>']);
    if (!$long) {
      return $output;
    }

    $output = '<p>' . $output . '</p>';
    $output .= '<p>' . t('Start a multi-column list with %start and end it with %end.  The 3 is just for the sake of example.  This will create 3 unordered lists and place them side by side.  One list item will be created for each line between the start and end tags, so do not add %li nor %endli tags yourself.  In addition to %cols, you can specify %type to be one of %types.',
      [
        '%start' => '<multicolumn cols="3">',
        '%end' => '</multicolumn>',
        '%li' => '<li>',
        '%endli' => '</li>',
        '%cols' => 'cols',
        '%type' => 'type',
        '%types' => "'ul', 'ol', 'plain'",
      ]) . '</p>';
    $output .= '<p>' . t('Any other attributes in the multicolumn tag will be passed to the lists that make up each column, unless a custom theme function changes this behavior.  Attributes and their values should contain only alphanumeric characters and underscores.') . '</p>';
    return $output;
  }

}
