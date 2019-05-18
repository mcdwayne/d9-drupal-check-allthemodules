<?php

namespace Drupal\bulk_term_merge;
use Drupal\Core\Database\Connection;

/**
 * Class DuplicateFinderService.
 */
class DuplicateFinderService implements DuplicateFinderServiceInterface {

  /**
   * Drupal\Core\Database\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a new DuplicateFinderService object.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public function findDuplicates(string $vid) {
    $duplicates = $this->connection->query('SELECT vid, name, count(*) FROM {taxonomy_term_field_data} GROUP BY vid, name HAVING count(*) > 1 AND vid = :vid', [
      ':vid' => $vid,
    ])->fetchAll();

    return $duplicates;
  }

  /**
   * {@inheritdoc}
   */
  public function getTermIds(string $name, string $vid) {
    $ids = $this->connection->query('SELECT tid FROM {taxonomy_term_field_data} WHERE name = :name AND vid = :vid', [
      ':name' => $name,
      ':vid' => $vid,
    ])->fetchAll();

    return $ids;
  }

}
