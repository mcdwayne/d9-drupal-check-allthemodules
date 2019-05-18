<?php

/**
 * @file
 * Contains Drupal\inline\Parser\DOMParser.
 */

namespace Drupal\inline\Parser;

use Drupal\inline\MacroInterface;

class DOMParser implements ParserInterface {

  public function parse($text, array $implementations) {
    $tagnames = '(' . implode('|', array_keys($implementations)) . ')';
    $match_count = preg_match_all('@\</?' . $tagnames . '((\s+\w+(\s*=\s*(?:".*?"|\'.*?\'|[^\'">\s]+))?)+\s*|\s*)/?>@', $text, $matches);
    $tags = array_unique($matches[2]);

    $dom = new \DOMDocument();
    $macros = array();
    for ($i = 0; $i < $match_count; $i++) {
      $source = $matches[0][$i];
      $type = $matches[1][$i];

      @$dom->loadHTML($source);
      $nodes = $dom->getElementsByTagName($type);

      foreach ($nodes as $node) {
        // @todo Handle non-existing types.
        $macro = new $implementations[$type]['class']();
        $args = $macro->getParameters();

        foreach ($args as $key => $info) {
          if (!$node->hasAttribute($key)) {
            continue;
          }
          $value = trim($node->getAttribute($key));
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
        $macros[$source] = $macro;
      }
    }

    return $macros;
  }

  public function serialize(MacroInterface $macro) {
    $macro_params = array();
    $macro_params[] = $macro->getType();
    foreach ($macro->params as $key => $value) {
      $macro_params[] = $key . '="' . $value . '"';
    }
    return '<' . implode(' ', $macro_params) . '>';
  }
}
