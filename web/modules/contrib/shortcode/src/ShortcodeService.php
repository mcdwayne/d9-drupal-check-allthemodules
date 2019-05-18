<?php

namespace Drupal\shortcode;

use Drupal\filter\Plugin\FilterInterface;
use Drupal\Core\Language\Language;
use Drupal\shortcode\ShortcodePluginManager;

/**
 * Provide the ShortCode service.
 */
class ShortcodeService {

  /**
   * The devel dumper plugin manager.
   *
   * @var \Drupal\devel\DevelDumperPluginManagerInterface
   */
  protected $shortCodePluginManager;

  /**
   * Constructs a ShortcodeService object.
   *
   * @param \Drupal\shortcode\ShortcodePluginManager $shortCodePluginManager
   *   The config factory service.
   */
  public function __construct(ShortcodePluginManager $shortCodePluginManager) {
    $this->shortCodePluginManager = $shortCodePluginManager;
  }

  /**
   * Returns preprocessed ShortCode plugin definitions.
   *
   * @return array
   *   Array of ShortCode plugin definitions.
   */
  public function loadShortcodePlugins() {

    /** @var \Drupal\Component\Plugin\PluginManagerInterface $type */
    $definitions_raw = $this->shortCodePluginManager->getDefinitions();

    $definitions = [];
    foreach ($definitions_raw as $shortcode_id => $definition) {

      // Default weight to 99.
      if (!isset($definition['weight'])) {
        $definition['weight'] = 99;
      }

      // Default token to id.
      $token = strtolower(isset($definition['token']) ? $definition['token'] : $shortcode_id);
      $definition['token'] = $token;

      $definitions[$shortcode_id] = $definition;
    }

    return $definitions;
  }

  /**
   * Returns array of shortcode plugin definitions enabled for the filter.
   *
   * @param bool $reset
   *   TRUE if the static cache should be reset. Defaults to FALSE.
   *
   * @return array
   *   Array of shortcode plugin definitions.
   */
  public function getShortcodePluginTokens($reset = FALSE) {
    $shortcode_tokens = &drupal_static(__FUNCTION__);

    // Prime plugin cache.
    if (!isset($shortcode_tokens) || $reset) {
      $shortcodes = $this->loadShortcodePlugins();
      $tokens = [];
      foreach ($shortcodes as $shortcode) {
        $tokens[$shortcode['token']] = $shortcode['token'];
      }
      $shortcode_tokens = $tokens;
    }

    return $shortcode_tokens;
  }

  /**
   * Returns array of shortcode plugin definitions enabled for the filter.
   *
   * @param \Drupal\filter\Plugin\FilterInterface $filter
   *   The filter. Defaults to NULL, where all shortcode plugins will be
   *   returned.
   * @param bool $reset
   *   TRUE if the static cache should be reset. Defaults to FALSE.
   *
   * @return array
   *   Array of shortcode plugin definitions, keyed by token, not id.
   */
  public function getShortcodePlugins(FilterInterface $filter = NULL, $reset = FALSE) {
    $shortcodes = &drupal_static(__FUNCTION__);

    // Prime plugin cache.
    if (!isset($shortcodes) || $reset) {

      $shortcodes_by_id = $this->loadShortcodePlugins();

      // Sorting plugins by weight using uasort() costs quite a bit more than
      // what we're doing below.
      $definitions = [];
      foreach ($shortcodes_by_id as $shortcode) {
        $token = $shortcode['token'];
        // Only replace the definition if weight is smaller.
        if (!isset($definitions[$token]) || ($definitions[$token]['weight'] < $shortcode['weight'])) {
          $definitions[$token] = $shortcode;
        }
      }

      $shortcodes = [
        'all' => $shortcodes_by_id,
        'default' => $definitions,
      ];
    }

    // If filter is given, only return plugin definitions enabled on the filter.
    if ($filter) {
      $filter_id = $filter->getPluginId();
      if (!isset($shortcodes[$filter_id])) {
        $settings = $filter->settings;

        $shortcodes_by_id = $shortcodes['all'];
        $enabled_shortcodes = [];
        foreach ($shortcodes_by_id as $shortcode_id => $shortcode) {
          if (isset($settings[$shortcode_id]) && $settings[$shortcode_id]) {
            $token = $shortcode['token'];
            // Only replace the definition if weight is smaller.
            if (!isset($enabled_shortcodes[$token]) || ($enabled_shortcodes[$token]['weight'] < $shortcode['weight'])) {
              $enabled_shortcodes[$token] = $shortcode;
            }
          }
        }

        $shortcodes[$filter_id] = $enabled_shortcodes;
      }

      return $shortcodes[$filter_id];
    }

    // Return all defined shortcode plugin definitions keyed by token.
    return $shortcodes['default'];
  }

  /**
   * Creates ShortCode plugin instance or loads from static cache.
   *
   * @param string $shortcode_id
   *   The shorShortCodetcode plugin id.
   *
   * @return \Drupal\shortcode\Plugin\ShortcodeInterface
   *   The plugin instance.
   */
  public function getShortcodePlugin($shortcode_id) {
    $plugins = &drupal_static(__FUNCTION__, []);
    if (!isset($plugins[$shortcode_id])) {

      /** @var \Drupal\shortcode\Shortcode\ShortcodePluginManager $type */
      $type = \Drupal::service('plugin.manager.shortcode');

      $plugins[$shortcode_id] = $type->createInstance($shortcode_id);
    }
    return $plugins[$shortcode_id];
  }

  /**
   * Checking the given tag is valid ShortCode tag or not.
   *
   * @param string $tag
   *   The tag name.
   *
   * @return bool
   *   Returns TRUE if the given $tag is valid ShortCode tag.
   */
  public function isValidShortcodeTag($tag) {
    $tokens = $this->getShortcodePluginTokens();
    // TODO: This is case-sensitive right now, consider if it should be.
    return isset($tokens[$tag]);
  }

  /**
   * Helper function to decide the given param is a bool value.
   *
   * @param mixed $var
   *   The variable.
   *
   * @return bool
   *   TRUE if $var is a booleany value.
   */
  protected function isBool($var) {
    switch (strtolower($var)) {
      case FALSE:
      case 'false':
      case 'no':
      case '0':
        $res = FALSE;
        break;

      default:
        $res = TRUE;
        break;
    }

    return $res;
  }

  /**
   * Processes the Shortcodes according to the text and the text format.
   *
   * @param string $text
   *   The string containing shortcodes to be processed.
   * @param string $langcode
   *   The language code of the text to be filtered.
   * @param \Drupal\filter\Plugin\FilterInterface $filter
   *   The text filter.
   *
   * @return string
   *   The processed string.
   */
  public function process($text, $langcode = Language::LANGCODE_NOT_SPECIFIED, FilterInterface $filter = NULL) {
    $shortcodes = $this->getShortcodePlugins($filter);

    // Processing recursively, now embedding tags within other tags is
    // supported!
    $chunks = preg_split('!(\[{1,2}.*?\]{1,2})!', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

    $heap = [];
    $heap_index = [];

    foreach ($chunks as $c) {

      if (!$c) {
        continue;
      }

      $escaped = FALSE;

      if ((substr($c, 0, 2) == '[[') && (substr($c, -2, 2) == ']]')) {
        $escaped = TRUE;
        // Checks media tags, eg: [[{ }]].
        if ((substr($c, 0, 3) != '{') && (substr($c, -3, 1) != '}')) {
          // Removes the outer [].
          $c = substr($c, 1, -1);
        }
      }
      // Decide this is a Shortcode tag or not.
      if (!$escaped && ($c[0] == '[') && (substr($c, -1, 1) == ']')) {
        // The $c maybe contains Shortcode macro.
        // This is maybe a self-closing tag.
        // Removes outer [].
        $original_text = $c;
        $c = substr($c, 1, -1);
        $c = trim($c);

        $ts = explode(' ', $c);
        $tag = array_shift($ts);
        $tag = trim($tag, '/');

        if (!$this->isValidShortcodeTag($tag)) {
          // This is not a valid shortcode tag, or the tag is not enabled.
          array_unshift($heap_index, '_string_');
          array_unshift($heap, $original_text);
        }
        // This is a valid shortcode tag, and self-closing.
        elseif (substr($c, -1, 1) == '/') {
          // Processes a self closing tag, - it has "/" at the end-
          /*
           * The exploded array elements meaning:
           * 0 - the full tag text?
           * 1/5 - An extra [] to allow for escaping Shortcodes with double
           * [[]].
           * 2 - The Shortcode name.
           * 3 - The Shortcode argument list.
           * 4 - The content of a Shortcode when it wraps some content.
           */

          $m = [
            $c,
            '',
            $tag,
            implode(' ', $ts),
            NULL,
            '',
          ];
          array_unshift($heap_index, '_string_');
          array_unshift($heap, $this->processTag($m, $shortcodes));
        }
        // A closing tag, we can process the heap.
        elseif ($c[0] == '/') {
          $closing_tag = substr($c, 1);

          $process_heap = [];
          $process_heap_index = [];
          $found = FALSE;

          // Get elements from heap and process.
          do {
            $tag = array_shift($heap_index);
            $heap_text = array_shift($heap);

            if ($closing_tag == $tag) {
              // Process the whole tag.
              $m = [
                $tag . ' ' . $heap_text,
                '',
                $tag,
                $heap_text,
                implode('', $process_heap),
                '',
              ];
              $str = $this->processTag($m, $shortcodes);
              array_unshift($heap_index, '_string_');
              array_unshift($heap, $str);
              $found = TRUE;
            }
            else {
              array_unshift($process_heap, $heap_text);
              array_unshift($process_heap_index, $tag);
            }
          } while (!$found && $heap);

          if (!$found) {
            foreach ($process_heap as $val) {
              array_unshift($heap, $val);
            }
            foreach ($process_heap_index as $val) {
              array_unshift($heap_index, $val);
            }
          }

        }
        // A starting tag. Add into the heap.
        else {
          array_unshift($heap_index, $tag);
          array_unshift($heap, implode(' ', $ts));
        }
      }
      else {
        // Maybe not found a pair?
        array_unshift($heap_index, '_string_');
        array_unshift($heap, $c);
      }
      // End of foreach.
    }

    return (implode('', array_reverse($heap)));
  }

  /**
   * Provides Html corrector for wysiwyg editors.
   *
   * Correcting p elements around the divs. <div> elements are not allowed
   * in <p> so remove them.
   *
   * @param string $text
   *   Text to be processed.
   * @param string $langcode
   *   The language code of the text to be filtered.
   * @param \Drupal\filter\Plugin\FilterInterface $filter
   *   The filter plugin that triggered this process.
   *
   * @return string
   *   The processed string.
   */
  public function postprocessText($text, $langcode, FilterInterface $filter = NULL) {

    // preg_match_all('/<p>s.*<!--.*-->.*<div/isU', $text, $r);
    // dpm($r, '$r');
    // Take note these are disrupted by the comments inserted by twig debug
    // mode.
    $patterns = [
      '|#!#|is',
      '!<p>(&nbsp;|\s)*(<\/*div>)!is',
      '!<p>(&nbsp;|\s)*(<div)!is',
      // '!<p>(&nbsp;|\s)*(<!--(.*?)-->)*(<div)!is', // Trying to ignore HTML
      // comments.
      '!(<\/div.*?>)\s*</p>!is',
      '!(<div.*?>)\s*</p>!is',
    ];

    $replacements = [
      '',
      '\\2',
      '\\2',
      // '\\3',.
      '\\1',
      '\\1',
    ];
    return preg_replace($patterns, $replacements, $text);
  }

  /**
   * Regular Expression callable for do_shortcode() for calling Shortcode hook.
   *
   * See for details of the match array contents.
   *
   * @param array $m
   *   Regular expression match array.
   *
   *     0 - the full tag text?
   *     1/5 - An extra [ or ] to allow for escaping shortcodes with double [[]]
   *     2 - The Shortcode name
   *     3 - The Shortcode argument list
   *     4 - The content of a Shortcode when it wraps some content.
   * @param array $enabled_shortcodes
   *   Array of enabled shortcodes for the active text format.
   *
   * @return string|false
   *   FALSE on failure.
   */
  protected function processTag(array $m, array $enabled_shortcodes) {
    $shortcode_token = $m[2];

    $shortcode = NULL;
    if (isset($enabled_shortcodes[$shortcode_token])) {
      $shortcode_id = $enabled_shortcodes[$shortcode_token]['id'];
      $shortcode = $this->getShortcodePlugin($shortcode_id);
    }

    // If shortcode does not exist or is not enabled, return input sans tokens.
    if (empty($shortcode)) {
      // This is an enclosing tag, means extra parameter is present.
      if (!is_null($m[4])) {
        return $m[1] . $m[4] . $m[5];
      }
      // This is a self-closing tag.
      else {
        return $m[1] . $m[5];
      }
    }

    // Process if shortcode exists and enabled.
    $attr = $this->parseAttrs($m[3]);
    return $m[1] . $shortcode->process($attr, $m[4]) . $m[5];
  }

  /**
   * Retrieve all attributes from the Shortcodes tag.
   *
   * The attributes list has the attribute name as the key and the value of the
   * attribute as the value in the key/value pair. This allows for easier
   * retrieval of the attributes, since all attributes have to be known.
   *
   * @param string $text
   *   The Shortcode tag attribute line.
   *
   * @return array
   *   List of attributes and their value.
   */
  protected function parseAttrs($text) {
    $attributes = [];
    if (empty($text)) {
      return $attributes;
    }
    $pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
    $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
    $text = html_entity_decode($text);
    if (preg_match_all($pattern, $text, $match, PREG_SET_ORDER)) {
      foreach ($match as $m) {
        if (!empty($m[1])) {
          $attributes[strtolower($m[1])] = stripcslashes($m[2]);
        }
        elseif (!empty($m[3])) {
          $attributes[strtolower($m[3])] = stripcslashes($m[4]);
        }
        elseif (!empty($m[5])) {
          $attributes[strtolower($m[5])] = stripcslashes($m[6]);
        }
        elseif (isset($m[7]) and strlen($m[7])) {
          $attributes[] = stripcslashes($m[7]);
        }
        elseif (isset($m[8])) {
          $attributes[] = stripcslashes($m[8]);
        }
      }
    }
    else {
      $attributes = ltrim($text);
    }
    return $attributes;
  }

}
