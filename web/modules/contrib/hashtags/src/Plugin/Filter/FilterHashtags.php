<?php

namespace Drupal\hashtags\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;
use Drupal\hashtags\Plugin\Filter\LinksConverter;

/**
 * @Filter(
 *   id = "filter_hashtags",
 *   title = @Translation("Hashtags Filter"),
 *   description = @Translation("Turn hashtags into links to corresponding terms"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_MARKUP_LANGUAGE,
 * )
 */
class FilterHashtags extends FilterBase {

    /**
     * Performs the filter processing.
     * @param string $text
     *   The text string to be filtered.
     * @param string $langcode
     *   The language code of the text to be filtered.
     * @return \Drupal\filter\FilterProcessResult
     *   The filtered text, wrapped in a FilterProcessResult object, and possibly
     *   with associated assets, cacheability metadata and placeholders.
     * @see \Drupal\filter\FilterProcessResult
     */
    public function process($text, $langcode) {
        $tids = _hashtags_get_tids_by_text($text);
        if (empty($tids)) {
            return new FilterProcessResult($text);
        }
        // create a class to pass parameters and have replace logic
        $replace_parameter = new LinksConverter($tids);
        // 1) 2+ character after #
        // 2) Don't start with or use only numbers (0-9) (#123abc, #123 etc)
        // 3) Letters - digits work correct (#abc123, #conference2013)
        // 4) No Special Characters “!, $, %, ^, &, *, +, .”
        // 5) No Spaces
        // 6) May use an underscore. Hyphens and dashes will not work.
        // 7) <p>#hashtag</p> - is valid
        // 8) <a href="#hashtag">Link</p> - is not valid
        // Bug when hashtag resides at the begining of the string
        $pattern = "/([\s>]*?)(#)([[:alpha:]][[:alnum:]_]*[^<\s[:punct:]])/iu";
        $text = preg_replace_callback($pattern, array(&$replace_parameter, 'replace'), $text);
        return new FilterProcessResult($text);
    }
}