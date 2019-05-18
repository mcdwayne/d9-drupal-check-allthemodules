<?php

namespace Drupal\migrate_plugins\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Drupal\migrate\Plugin\MigrateProcessInterface;

/**
 * Provides a 'SkipEmptyVocabulary' migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *  id = "d7_skip_empty_vocabulary"
 * )
 */
class SkipEmptyVocabulary extends DrupalSqlBase implements MigrateProcessInterface {

  /**
   * {@inheritdoc}
   */
  public function query() {
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!$value) {
      $message = "Skipped vocabulary with empty ID.";
      throw new MigrateSkipRowException($message);
    }

    $query = $this->select('taxonomy_term_data', 'td');
    $query->condition('td.vid', $value);
    $query->addExpression('COUNT(*)');

    // @var \Drupal\Core\Database\Statement $result
    $result = $query->execute();
    $count_terms = $result->fetchField();

    if (empty($count_terms)) {
      $message = "Skipped empty terms vocabulary ID #$value";
      throw new MigrateSkipRowException($message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return FALSE;
  }

}
