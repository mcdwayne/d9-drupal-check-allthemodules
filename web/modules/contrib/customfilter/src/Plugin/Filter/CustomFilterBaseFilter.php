<?php

namespace Drupal\customfilter\Plugin\Filter;

use Drupal\customfilter\Entity\CustomFilter;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\FilterProcessResult;

/**
 * Provides a base filter for Custom Filter.
 *
 * No annotation here, see info alter hook.
 *
 * @see \customfilter_filter_info_alter
 */
class CustomFilterBaseFilter extends FilterBase {

  /**
   * Performs the filter processing.
   *
   * @param string $text
   *   The text string to be filtered.
   * @param string $langcode
   *   The language code of the text to be filtered.
   */
  public function process($text, $langcode) {
    // If the text passed is an empty string, then return it immediately.
    if (empty($text)) {
      return '';
    }
    $entity = CustomFilter::load($this->settings['id']);
    $globals = &static::getGlobals('push');
    $globals->text = $text;

    $rules = $entity->getRulesTree();
    if (is_array($rules) && count($rules)) {
      // Reset the object containing the global variables.
      static::getCodeVars(TRUE);

      // Prepare the stack used to save the parent rule.
      $globals->stack = array();

      foreach ($rules as $rule) {
        if ($rule['enabled']) {
          $globals->stack[] = $rule;
          $globals->text = preg_replace_callback($rule['pattern'], [CustomFilterBaseFilter::class, 'applyRules'], $globals->text);

          array_pop($globals->stack);
        }
      }
    }

    $text = $globals->text;
    static::getGlobals('pop');

    $result = new FilterProcessResult($text);
    $cache_tags = array('customfilter:' . $this->settings['id']);
    $result->addCacheTags($cache_tags);

    return $result;
  }

  /**
   * Get the tips for the filter.
   *
   * @param bool $long
   *   If get the long or short tip.
   *
   * @return string
   *   The tip to show for the user.
   */
  public function tips($long = FALSE) {
    $entity = CustomFilter::load($this->settings['id']);
    if ($long) {
      return $entity->getLongtip();
    }
    else {
      return $entity->getShorttip();
    }
  }

  /**
   * Replace the text using rules.
   *
   * @param array $matches
   *   The text match by regular expression.
   *
   * @return string
   *   The text after rules have beem apply.
   */
  public static function applyRules(array $matches) {
    $globals = &static::getGlobals();
    $result = $matches[0];
    $rule = end($globals->stack);

    $code = $rule['code'];
    $pattern = $rule['pattern'];
    $replacement = $rule['replacement'];

    if (is_array($sub = $rule['sub']) && count($sub)) {
      foreach ($sub as $subrule) {
        if ($subrule['enabled']) {
          $globals->stack[] = $subrule;

          $substr = & $matches[$subrule['matches']];
          $substr = preg_replace_callback($subrule['pattern'], [CustomFilterBaseFilter::class, 'applyRules'], $substr);

          array_pop($globals->stack);
        }
      }

      if ($code) {
        CustomFilterBaseFilter::replaceCallback($replacement, TRUE);
        $result = CustomFilterBaseFilter::replaceCallback($matches);
      }
      else {
        $result = $replacement;

        $rmatches = array();
        $reps = array();

        preg_match_all('/([^\\\\]|^)(\$([0-9]{1,2}|\{([0-9]{1,2})\}))/', $replacement, $rmatches, PREG_OFFSET_CAPTURE);

        foreach ($rmatches[4] as $key => $val) {
          if ($val == '') {
            $index = $rmatches[3][$key][0];
          }
          else {
            $index = $rmatches[4][$key][0];
          }

          $offset = $rmatches[2][$key][1];
          $length = strlen($rmatches[2][$key][0]);

          $reps[] = array(
            'index' => $index,
            'offset' => $offset,
            'length' => $length,
          );
        }

        krsort($reps);

        foreach ($reps as $rep) {
          $result = substr_replace($result, $matches[$rep['index']], $rep['offset'], $rep['length']);
        }
      }
    }
    elseif ($code) {
      CustomFilterBaseFilter::replaceCallback($replacement, TRUE);
      $result = preg_replace_callback($pattern, [CustomFilterBaseFilter::class, 'replaceCallback'], $result);
    }
    else {
      $result = preg_replace($pattern, $replacement, $result);
    }

    return $result;
  }

  /**
   * Helper function for preg_replace_callback().
   */
  public static function replaceCallback($matches, $init = FALSE) {
    static $code;

    if ($init) {
      $code = $matches;
      return;
    }

    $vars = & static::getCodeVars();

    @eval($code);

    return isset($result) ? $result : '';
  }

  /**
   * Return the global object containing the global properties.
   *
   * Return the global object containing the global properties used in the
   * replacement PHP code.
   *
   * @param bool $reset
   *   Boolean value set to TRUE when the global object must be reset.
   */
  public static function &getCodeVars($reset = FALSE) {
    static $vars;

    if (!isset($vars) || $reset) {
      $vars = new \stdClass();
    }

    return $vars;
  }

  /**
   * Return an object with global variables used during the execution of a rule.
   */
  public static function &getGlobals($op = '') {
    static $globals = array(), $index = 0;

    if ($op == 'push') {
      $globals[++$index] = new \stdClass();
    }
    elseif ($op == 'pop' && $index) {
      unset($globals[$index--]);
    }

    return $globals[$index];
  }

}
