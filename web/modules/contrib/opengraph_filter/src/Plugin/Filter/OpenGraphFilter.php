<?php

/**
 * @file
 * Contains Drupal\opengraph_filter\Plugin\Filter\OpenGraphFilter
 */

namespace Drupal\opengraph_filter\Plugin\Filter;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;
use Drupal\Component\Utility\Unicode;

/**
 * OpenGraph Filter
 *
 * @Filter (
 *    id = "opengraph_filter",
 *    title = @Translation("Opengraph Filter"),
 *    description = @Translation("Provide opengraph filter"),
 *    type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 *  )
 */
class OpenGraphFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {

    // Tags to skip and not recurse into.
    $ignore_tags = 'a|script|style|code|pre';

    // Create an array which contains the regexps for each type of link.
    // The key to the regexp is the name of a function that is used as
    // callback function to process matches of the regexp. The callback function
    // is to return the replacement for the match. The array is used and
    // matching/replacement done below inside some loops.
    $tasks = array();

    // Prepare protocols pattern for absolute URLs.
    // check_url() will replace any bad protocols with HTTP, so we need to support
    // the identical list. While '//' is technically optional for MAILTO only,
    // we cannot cleanly differ between protocols here without hard-coding MAILTO,
    // so '//' is optional for all protocols.
    // @see filter_xss_bad_protocol()
    $protocols = array('http', 'https');
    $protocols = implode(':(?://)?|', $protocols) . ':(?://)?';

    // Prepare domain name pattern.
    // The ICANN seems to be on track towards accepting more diverse top level
    // domains, so this pattern has been "future-proofed" to allow for TLDs
    // of length 2-64.
    $domain = '(?:[A-Za-z0-9._+-]+\.)?[A-Za-z]{2,64}\b';
    $ip = '(?:[0-9]{1,3}\.){3}[0-9]{1,3}';
    $auth = '[a-zA-Z0-9:%_+*~#?&=.,/;-]+@';
    $trail = '[a-zA-Z0-9:%_+*~#&\[\]=/;?!\.,-]*[a-zA-Z0-9:%_+*~#&\[\]=/;-]';

    // Prepare pattern for optional trailing punctuation.
    // Even these characters could have a valid meaning for the URL, such usage is
    // rare compared to using a URL at the end of or within a sentence, so these
    // trailing characters are optionally excluded.
    $punctuation = '[\.,?!]*?';

    // Match absolute URLs.
    $url_pattern = "(?:$auth)?(?:$domain|$ip)/?(?:$trail)?";
    $pattern = "`((?:$protocols)(?:$url_pattern))($punctuation)`";
    $tasks['_filter_url_parse_full_links'] = $pattern;

    // Match www domains.
    $url_pattern = "www\.(?:$domain)/?(?:$trail)?";
    $pattern = "`($url_pattern)($punctuation)`";
    $tasks['_filter_url_parse_partial_links'] = $pattern;

    // Each type of URL needs to be processed separately. The text is joined and
    // re-split after each task, since all injected HTML tags must be correctly
    // protected before the next task.
    $urls = array();
    foreach ($tasks as $task => $pattern) {
      // HTML comments need to be handled separately, as they may contain HTML
      // markup, especially a '>'. Therefore, remove all comment contents and add
      // them back later.
      _filter_url_escape_comments('', TRUE);
      $text = preg_replace_callback('`<!--(.*?)-->`s', '_filter_url_escape_comments', $text);

      // Split at all tags; ensures that no tags or attributes are processed.
      $chunks = preg_split('/(<.+?>)/is', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
      // PHP ensures that the array consists of alternating delimiters and
      // literals, and begins and ends with a literal (inserting NULL as
      // required). Therefore, the first chunk is always text:
      $chunk_type = 'text';
      // If a tag of $ignore_tags is found, it is stored in $open_tag and only
      // removed when the closing tag is found. Until the closing tag is found,
      // no replacements are made.
      $open_tag = '';

      for ($i = 0; $i < count($chunks); $i++) {
        if ($chunk_type == 'text' || ($chunk_type == 'tag' && substr(strtolower($chunks[$i]), 0, 3) == '<a ')) {
          // Only process this text if there are no unclosed $ignore_tags.
          if ($open_tag == '') {
            // If there is a match, add to urls array.
            if (preg_match_all($pattern, $chunks[$i], $found, PREG_PATTERN_ORDER)) {
              $num_found = count($found[1]);
              for ($j = 0; $j < $num_found; $j++) {
                if ($task == '_filter_url_parse_partial_links') {
                  $urls[] = 'http://' . $found[1][$j];
                }
                else {
                  $urls[] = $found[1][$j];
                }
              }
            }
          }
          // Text chunk is done, so next chunk must be a tag.
          $chunk_type = 'tag';
        }
        else {

          // Only process this tag if there are no unclosed $ignore_tags.
          if ($open_tag == '') {
            // Check whether this tag is contained in $ignore_tags.
            if (preg_match("`<($ignore_tags)(?:\s|>)`i", $chunks[$i], $matches)) {
              $open_tag = $matches[1];
            }
          }
          // Otherwise, check whether this is the closing tag for $open_tag.
          else {
            if (preg_match("`<\/$open_tag>`i", $chunks[$i], $matches)) {
              $open_tag = '';
            }
          }
          // Tag chunk is done, so next chunk must be text.
          $chunk_type = 'text';
        }
      }

      $text = implode($chunks);
      // Revert back to the original comment contents
      _filter_url_escape_comments('', FALSE);
      $text = preg_replace_callback('`<!--(.*?)-->`', '_filter_url_escape_comments', $text);
    }

    $urls = array_values(array_unique($urls));
    $urls_found = count($urls);
    $processed = 0;

    for ($i = 0; $i < $urls_found; $i++) {

      $element = [
        '#theme' => 'opengraph_filter',
        '#uri' => $urls[$i],
        '#imagestyle' => !empty($this->settings['opengraph_filter_imagestyle'])?$this->settings['opengraph_filter_imagestyle']:'',
        '#attached' => [
          'library' => [
            'opengraph_filter/opengraph-filter',
          ]
        ],
      ];

      $html = \Drupal::service('renderer')->render($element);

      if (Unicode::strlen($html)) {
        $text .= $html;
        $processed++;
      }
      if ($processed >= $this->settings['opengraph_filter_num']) {
        break;
      }
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $form['opengraph_filter_num'] = [
      '#type' => 'textfield',
      '#title' => t('Number of previews'),
      '#default_value' => $this->settings['opengraph_filter_num'],
      '#size' => 5,
      '#maxlength' => 4,
      '#field_suffix' => $this->t('previews'),
      '#description' => $this->t('The number of previews that will be add. The URL that is first found will be processed first. A preview will be genereated from a succesfull call for metatags.'),
    ];
    
    return $form;
  }


}