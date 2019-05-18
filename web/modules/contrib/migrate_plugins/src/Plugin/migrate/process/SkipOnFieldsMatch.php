<?php

namespace Drupal\migrate_plugins\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Provides a 'SkipOnFieldsMatch' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "skip_on_fields_match"
 * )
 */
class SkipOnFieldsMatch extends ProcessPluginBase {

  /**
   * The migration to be executed.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $source = $this->migration->getSourcePlugin();
    $current_row = $row;

    // By default use the input row.
    if (!isset($this->configuration['use_parent_row'])) {
      $this->configuration['use_parent_row'] = FALSE;
    }

    // By default do not negate.
    if (!isset($this->configuration['negate'])) {
      $this->configuration['negate'] = FALSE;
    }

    // Parent row is useful on sub_process iteration where row input will point
    // to the iteration new row.
    if ($this->configuration['use_parent_row']) {
      // @var \Drupal\migrate\Row $current_row
      $current_row = $source->current();
    }

    // Get the target value from current row.
    $field_name = $this->configuration['target_field_name'];
    $target_value = $current_row->getSourceProperty($field_name);

    // On negation, skip when unmatch.
    if ($this->configuration['negate'] && $value != $target_value) {
      $message = "Skip process on unmatch {$value} != {$target_value}";
      throw new MigrateSkipProcessException($message);
    }

    // On normal, skip when match.
    if (!$this->configuration['negate'] && $value == $target_value) {
      $message = "Skip process on match {$value} = {$target_value}";
      throw new MigrateSkipProcessException($message);
    }

    return $value;
  }

}
