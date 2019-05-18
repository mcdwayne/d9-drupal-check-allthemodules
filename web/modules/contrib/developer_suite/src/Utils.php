<?php

namespace Drupal\developer_suite;

use Drupal\Component\Utility\Unicode;

/**
 * Class Utils.
 *
 * Provides helper methods.
 *
 * @package Drupal\developer_suite
 */
class Utils {

  /**
   * Returns an ID from a fully qualified class name.
   *
   * For example: the \Drupal\developer_suite_examples\Form\ExampleForm becomes
   * 'developer_suite_examples_form_example_form'.
   *
   * @param string $class
   *   The fully qualified class name.
   *
   * @return string
   *   The replaced class ID.
   */
  public static function getClassId($class) {
    $replaceString = str_replace(['Drupal\\', '\\'], '', $class);
    $returnFormId = '';

    for ($i = 0; $i < strlen($replaceString); $i++) {
      if ($i === 0) {
        $returnFormId .= $replaceString[$i];
      }
      else {
        $returnFormId .= ctype_upper($replaceString[$i]) ? "_{$replaceString[$i]}" : $replaceString[$i];
      }
    }

    return Unicode::strtolower($returnFormId);
  }

}
