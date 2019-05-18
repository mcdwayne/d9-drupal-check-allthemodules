<?php

/**
 * @file
 * Contains Drupal\inline\Parser\JSONParser.
 */

namespace Drupal\inline\Parser;

use Drupal\inline\MacroInterface;

class JSONParser implements ParserInterface {

  public function parse($text, array $implementations) {
    $match_count = preg_match_all('@\{.*\}@', $text, $matches);

    $macros = array();
    for ($i = 0; $i < $match_count; $i++) {
      $source = $matches[0][$i];

      $parsed = json_decode($source);
      // @todo Handle decoding errors.
      if (empty($parsed)) {
        continue;
      }

      // @todo Handle non-existing types.
      $macro = new $implementations[$parsed->tag]['class']();

      $args = $macro->getParameters();
      foreach ($parsed->params as $key => $value) {
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
      // Fill in defaults.
      foreach ($args as $key => $info) {
        if (!isset($macro->params[$key]) && isset($info['#default_value'])) {
          $macro->params[$key] = $info['#default_value'];
        }
      }
      // The full unaltered tag is the key for the filter attributes array.
      $macros[$matches[0][$i]] = $macro;
    }

    return $macros;
  }

  public function serialize(MacroInterface $macro) {
    $data = array('tag' => $macro->getType(), 'params' => $macro->params);
    return json_encode($data, JSON_FORCE_OBJECT);
  }
}
