<?php

/**
 * @file
 * Contains \Drupal\demo_content\DemoContentParserYAML.
 */

namespace Drupal\demo_content;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Core\Extension\InfoParserException;

/**
 * Class DemoContentParserYAML
 * 
 * @package Drupal\demo_content
 */
class DemoContentParserYAML implements DemoContentParserInterface {

  /**
   * @inheritdoc
   */
  public function parse($filename, array $replacements = []) {
    if (!file_exists($filename)) {
      $parsed_info = array();
    }
    else {
      try {
        $content = file_get_contents($filename);

        // Perform replacements.
        if (count($replacements)) {
          $content = str_replace(array_keys($replacements), array_values($replacements), $content);
        }

        $parsed_info = Yaml::decode($content);
      }
      catch (InvalidDataTypeException $e) {
        throw new InfoParserException("Unable to parse $filename " . $e->getMessage());
      }
      $missing_keys = array_diff($this->getRequiredKeys(), array_keys($parsed_info));
      if (!empty($missing_keys)) {
        throw new InfoParserException('Missing required keys (' . implode(', ', $missing_keys) . ') in ' . $filename);
      }
    }

    return $parsed_info;
  }


  /**
   * Returns an array of keys required to exist in .info.yml file.
   *
   * @return array
   *   An array of required keys.
   */
  protected function getRequiredKeys() {
    return array('entity_type', 'bundle', 'content');
  }
}
