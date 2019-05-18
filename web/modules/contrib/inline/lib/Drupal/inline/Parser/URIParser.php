<?php

/**
 * @file
 * Contains Drupal\inline\Parser\URIParser.
 */

namespace Drupal\inline\Parser;

use Drupal\inline\MacroInterface;

class URIParser implements ParserInterface {

  public function parse($text, array $implementations) {
    $tagnames = '(' . implode('|', array_keys($implementations)) . ')';
    $match_count = preg_match_all('@\[' . $tagnames . '://(.*)]@', $text, $matches);

    $macros = array();
    for ($i = 0; $i < $match_count; $i++) {
      $uri = $matches[0][$i];
      $type = $matches[1][$i];

      $parameters = html_entity_decode($matches[2][$i]);
      $components = array();
      parse_str($parameters, $components);

      foreach ($components as $component) {
        // @todo Handle non-existing types.
        $macro = new $implementations[$type]['class']();
        $args = $macro->getParameters();

        foreach ($args as $key => $info) {
          if (!array_key_exists($key, $components)) {
            continue;
          }
          $value = trim($components[$key]);
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
        $macros[$uri] = $macro;
      }
    }

    return $macros;
  }

  public function serialize(MacroInterface $macro) {
    return '[' . $macro->getType() . '://' . $this->query_str($macro->params) . ']';
  }

  protected function query_str($params) {
    $str = '';
    foreach ($params as $key => $value) {
      $str .= (strlen($str) < 1) ? '' : '&';
      $str .= $key . '=' . rawurlencode($value);
    }
    return $str;
  }
}
