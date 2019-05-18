<?php

/**
 * @file
 * Contains Drupal\inline\Parser\DefaultParser.
 */

namespace Drupal\inline\Parser;

use Drupal\inline\MacroInterface;

class DefaultParser implements ParserInterface {

  public function parse($text, array $implementations) {
    $tagnames = '(' . implode('|', array_keys($implementations)) . ')';
    // @todo Add support for escaped [, ] chars
    preg_match_all('@\[' . $tagnames . '\s*(?:\|([^\[\]]+))?\]@', $text, $matches);
    // Don't process duplicates.
    $tags = array_unique($matches[2]);

    $macros = array();
    foreach ($tags as $n => $parameters) {
      $type = $matches[1][$n];
      // @todo Handle non-existing types.
      $macro = new $implementations[$type]['class']();

      $args = $macro->getParameters();
      if (!empty($parameters)) {
        // @todo Add support for escaped | characters.
        $macro_params = array_map('trim', explode('|', $parameters));
        // @todo Add a macro counter for each found tag *per module* to allow stuff
        //   like odd/even classes (f.e. $macro['#count']).
        foreach ($macro_params as $param) {
          list($key, $value) = explode('=', $param, 2);
          $key = trim($key);

          // All parameter values are strings by default.
          $value = trim($value);
          // Convert numeric values.
          if (is_numeric($value)) {
            if (strpos($value, '.') !== FALSE) {
              $value = (float) $value;
            }
            else {
              $value = (int) $value;
            }
          }
          // Convert boolean values.
          elseif (in_array(drupal_strtolower($value), array('true', 'false'))) {
            $value = (bool) $value;
          }
          // Stack multiple occurences.
          if (isset($macro->params[$key]) && !empty($args[$key]['#multiple'])) {
            if (!is_array($macro->params[$key])) {
              $macro->params[$key] = array($macro->params[$key]);
            }
            $macro->params[$key][] = $value;
          }
          else {
            $macro->params[$key] = $value;
          }
        }
      }
      // Fill in defaults.
      foreach ($args as $key => $info) {
        if (!isset($macro->params[$key]) && isset($info['#default_value'])) {
          $macro->params[$key] = $info['#default_value'];
        }
      }
      // The full unaltered tag is the key for the filter attributes array.
      $macros[$matches[0][$n]] = $macro;
    }

    return $macros;
  }

  public function serialize(MacroInterface $macro) {
    $macro_params = array();
    $macro_params[] = $macro->getType();
    foreach ($macro->params as $key => $value) {
      $macro_params[] = $key . '=' . $value;
    }
    return '[' . implode('|', $macro_params) . ']';
  }
}
