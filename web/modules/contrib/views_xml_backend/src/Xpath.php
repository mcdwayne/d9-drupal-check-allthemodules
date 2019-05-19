<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Xpath.
 */

namespace Drupal\views_xml_backend;

/**
 * Helper functions for handling XPath.
 */
class Xpath {

  /**
   * Escapes an XPath string.
   *
   * @param string $argument
   *   The string to escape.
   *
   * @return string
   *   The escaped string.
   */
  static public function escapeXpathString($argument) {
    if (strpos($argument, "'") === FALSE) {
      return "'" . $argument . "'";
    }

    if (strpos($argument, '"') === FALSE) {
      return '"' . $argument . '"';
    }

    $string = $argument;
    $parts = [];

    // XPath doesn't provide a way to escape quotes in strings, so we break up
    // the string and return a concat() function call.
    while (TRUE) {
      if (FALSE !== $pos = strpos($string, "'")) {
        $parts[] = sprintf("'%s'", substr($string, 0, $pos));
        $parts[] = "\"'\"";
        $string = substr($string, $pos + 1);
      }
      else {
        $parts[] = "'$string'";
        break;
      }
    }

    return sprintf('concat(%s)', implode($parts, ', '));
  }

}
