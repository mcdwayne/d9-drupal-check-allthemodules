<?php

namespace Drupal\jvector;

/**
 * Defines a default implementation for entity access controllers.
 * @todo Needs some cleanup
 */
class JvectorSvgReader { // implements JvectorSvgValidatorInterface

  /**
   * The variable holding the SVG code.
   */
  protected $svgCode = "";
  protected $svgArray = array();
  protected $valid = FALSE;

  /**
   * Get the svg_code and validate if autovalidate is true
   */
  public function __construct($svg_code, $autoValidate = TRUE) {
    // Remember the svg
    $this->svgCode = $svg_code;
    $this->validate();
  }

  public function validate() {
    // Try to convert to array.
    $converted = $this->convertSvg();
    if (is_array($converted) && !empty($converted)) {
      $this->converted = $converted;
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Convert SVG element to array.
   */
  public function convertSvg() {
    // Make sure the file was readable, if not die.
    $contents = $this->svgCode;


    // Get array of graphical paths from svg content.
    $graphical_paths = explode('<path', str_replace(array(
          "\r",
          "\r\n",
          "\n",
          "\t"
        ), '', $contents));
    // Iterates with every path in the svg file.
    $path_iterator = 0;

    // The output array.
    $numbered_paths = array();

    // Regex to match svg attributes.
    $regex = '/\b(id|data-code|fill|d|name|stroke|stroke-width)\b=["|\']([^"\']*)["|\']/';

    // Loops through paths.
    foreach ($graphical_paths as $path) {
      // If matche not found & match doesn't contains a path attr, skip.
      if (!preg_match_all($regex, $path, $matches) OR !in_array('d', $matches[1])) {
        continue;
      }

      // Iterates for every attr found.
      $attr_iterator = 0;

      // Loops through attributes and adds to output array.

      foreach ($matches[1] as $match) {
        $numbered_paths[$path_iterator][$match] = $matches[2][$attr_iterator];
        $attr_iterator++;
      }
      $path_iterator++;
    }
    $output = array();
    // Map the output with IDs first. 
    // We need those for element selection.
    foreach ($numbered_paths AS $numbered_path) {
      // We NEED ID for this to work.;
      $path_id = NULL;
      // Newer Jvector maps uses 'data-code' as identifier
      if (isset($numbered_path['data-code'])) {
        $path_id = $numbered_path['data-code'];
        // Regular SVGs uses ID
      }
      elseif (isset($numbered_path['id'])) {
        $path_id = $numbered_path['id'];
      }
      if (!empty($path_id)) {
        $output[$path_id] = $numbered_path;
        $output[$path_id]['id'] = $path_id;
      }
      // We need a name.
      if (!isset($output[$path_id]['name'])) {
        $output[$path_id]['name'] = $path_id;
      }
      // Rename 'd' to 'path'
      $output[$path_id]['path'] = $output[$path_id]['d'];
      unset($output[$path_id]['d']);
    }
    return $output;
  }

}