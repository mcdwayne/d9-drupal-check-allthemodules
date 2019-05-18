<?php

namespace Drupal\interface_string_stats;

use Drupal\Core\Database\Connection;
use Drupal\locale\StringDatabaseStorage;
use Drupal\locale\StringStorageInterface;
use Drupal\locale\TranslationString;

/**
 * Defines a class to store localized strings in the database.
 */
class StringStatsStringDatabaseStorage extends StringDatabaseStorage {

  /**
   * Original StringDatabaseStorage object.
   *
   * @var \Drupal\locale\StringStorageInterface
   */
  protected $stringDatabase;

  /**
   * Constructs a new StringDatabaseStorage class.
   *
   * @param \Drupal\locale\StringStorageInterface $string_database
   *   Original StringStorageInterface object.
   * @param \Drupal\Core\Database\Connection $connection
   *   A Database connection to use for reading and writing configuration data.
   * @param array $options
   *   (optional) Any additional database connection options to use in queries.
   */
  public function __construct(
    StringStorageInterface $string_database,
    Connection $connection,
    array $options = []
  ) {
    $this->stringDatabase = $string_database;
    parent::__construct($connection, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslations(array $conditions = [], array $options = []) {
    if (!isset($options['isf_query'])) {
      return $this->stringDatabase->getTranslations($conditions, $options);
    }

    unset($options['isf_query']);
    $strings = [];

    // Use the parent method to build the base query and then add our stats
    // table.
    $query = $this->dbStringSelect($conditions, ['translation' => TRUE] + $options);
    $query->addExpression('(SELECT COUNT(*) FROM `interface_string_stats` AS f WHERE f.lid = s.lid)', 'count');
    $query->orderBy('count', 'DESC');
    $result = $query->execute();

    foreach ($result as $item) {
      /** @var \Drupal\interface_string_stats\StringStatsTranslationString $string */
      $string = new StringStatsTranslationString($item);
      $string->setStorage($this);
      $strings[] = $string;
    }

    return $strings;
  }

  /**
   * {@inheritdoc}
   */
  public function findTranslation(array $conditions) {
    if (!isset($conditions['read_only'])) {
      return $this->stringDatabase->findTranslation($conditions);
    }

    unset($conditions['read_only']);
    $values = $this->dbStringSelect($conditions, ['translation' => TRUE])
      ->execute()
      ->fetchAssoc();

    if (!empty($values)) {
      $string = new TranslationString($values);
      return $string;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function dbStringSelect(array $conditions, array $options = []) {
    if (isset($conditions['read_only'])) {
      unset($conditions['read_only']);
    }
    return $this->stringDatabase->dbStringSelect($conditions, $options);
  }

}
