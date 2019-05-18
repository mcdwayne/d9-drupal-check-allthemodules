<?php

namespace Drupal\abbrfilter;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Defines the abbrfilter data service.
 */
class AbbrfilterData {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $configFactory;

  /**
   * Constructor function to inject configFactoryInterface. 
   */
  public function __construct(ConfigFactoryInterface $configFactoryInterface) {
    $this->configFactory = $configFactoryInterface->get('abbrlist.settings');
  }

  /**
   * Helper function: Query to get the list of abbrs to filter.
   *
   * @return
   *   a list of abbreviations to filter
   */
  public function get_abbr_list() {

    $list = $this->configFactory->get('abbr_list');

    return $list;
  }

  /**
   * Helper function: Query to get the filter text.
   *
   * @return filter text.
   */
  public function get_abbr_text($abbrfilter_id) {
    $list = $this->configFactory->get('abbr_list');
    
    return $list[$abbrfilter_id]['abbrs'];
  }

  /**
   * Helper function: Query to get the replace text.
   *
   * @return replace text.
   */
  public function get_abbr_replacetext($abbrfilter_id) {
    $list = $this->configFactory->get('abbr_list');
    
    return $list[$abbrfilter_id]['replacement'];
  }

  /**
   * Remove abbrfilter by given filter id.
   *
   * @return True if success otherwise eturn false.
   */
  public function del_abbr($abbrfilter_id) {
    $list = $this->configFactory->get('abbr_list');
    
    unset($list[$abbrfilter_id]);
    $this->configFactory->set('abbr_list', $list)->save();
  }

  /**
   * Perform the actual abbreviation substitution.
   *
   * We specifically match text that is outside of HTML tags
   * so that for example <img src="http://vhs.org/image.jpg" /> doesn't get the
   * 'VHS' part substituted as that would break the image. We use
   * preg_replace_callback to call our anonymous function for each matching
   * group that is found.
   *
   * In each match, we split on word boundaries, and then check each piece of the
   * split against the list of abbreviations.
   *
   * NB: The filter currently fails to detect that an abbreviation is already
   * surrounded by <abbr> tags.
   **/
  public function perform_subs($text, $list) {
    // We prepare a keyed array called $fast_array because this is the
    // quickest way to search later on (using isset()).
    $fast_array = array();
    foreach ($list as $item) {
      // We want to split on word boundaries, unfortunately PCRE considers words
      // to include underscores but not other characters like dashes and slashes,
      // so we have this hack that subs all characters we want to allow in
      // abbreviations and the target with this massive random blob of all word
      // characters, so that we can correctly split, switching it back later.
      $key = preg_replace('#-#u', '___999999DASH___', $item['abbrs']);
      $key = preg_replace('#/#u', '___111111SLASH___', $key);
      $fast_array[$key] = $item['replacement'];
    }

    // Provide an anonymous function for the preg_replace. This function gets
    // called a LOT, so be careful about optimization of anything that goes in
    // here.
    $callback = function($matches) use ($fast_array) {
      // Split the text into an array of words, on word boundaries.
      $words = preg_split('/\b/u', $matches[0]);

      // For each word, check if it matches our abbreviation filter.
      foreach ($words as $key => $word) {
        if (!empty($word)) {
          if (isset($fast_array[$word])) {
            $words[$key] = '<abbr title="' . $fast_array[$word] . '">' . $word . '</abbr>';
          }
        }
      }
      return implode('', $words);
    };

    $text = preg_replace('#-#u', '___999999DASH___', $text);
    $text = preg_replace('#/#u', '___111111SLASH___', $text);

    // Match all content that is not part of a tag, i.e. not between < and >.
    // (?:) = create a non-capturing group.
    // (?:^|>) = the beginning of the string or a closing HTML tag.
    // (?:[^<]|$)+ = characters that are not an opening tag or end of the string.
    //
    // Don't mess with this regular expression unless you understand the PCRE
    // stack limitations. Basically, removing the double plus signs causes a
    // stack overflow and thus a segmentation fault in PHP, as PCRE recurses too
    // deeply. @see http://www.manpagez.com/man/3/pcrestack/ the section on
    // reducing stack usage.
    $text = preg_replace_callback('/(?:^|>)++(?:[^<]++|$)+/u', $callback, $text);

    $text = preg_replace('/___999999DASH___/u', '-', $text);
    $text = preg_replace('/___111111SLASH___/u', '/', $text);

    return $text;
  }
}
