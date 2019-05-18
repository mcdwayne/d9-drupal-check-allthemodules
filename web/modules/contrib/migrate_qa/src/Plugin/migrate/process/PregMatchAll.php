<?php

namespace Drupal\migrate_qa\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Match multiples by a regex, returns an array of all matches.
 *
 * Example usage is if the target field is a multiple value text field.
 *
 * Available configuration keys:
 * - regex: Regular expression. The entire match is returned, not just the
 *   capture group identified by parentheses.
 *
 * Examples:
 *
 * @code
 * process:
 *   field_example_body:
 *     plugin: preg_match_all
 *     source: some_source_text_string
 *     regex: '(<iframe[^>]*>)'
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "preg_match_all"
 * )
 */
class PregMatchAll extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!isset($this->configuration['regex'])) {
      throw new MigrateException('preg_match_all plugin is missing regex configuration.');
    }
    if (!is_string($this->configuration['regex'])) {
      throw new MigrateException('preg_match_all plugin\'s regex configuration must be a string.');
    }
    $regex = $this->configuration['regex'];
    $matches = [];
    preg_match_all($regex, $value, $matches);
    if (empty($matches) || empty($matches[0])) {
      return '';
    }

    return $matches[0];
  }

}
