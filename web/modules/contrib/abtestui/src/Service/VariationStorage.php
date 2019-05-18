<?php

namespace Drupal\abtestui\Service;

use Drupal\Core\Database\Connection;

/**
 * Class VariationStorage.
 *
 * @package Drupal\abtestui\Service
 */
class VariationStorage {

  const BASE_TABLE_NAME = 'abtestui_variation';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $db;

  /**
   *
   */
  public function __construct(Connection $database) {
    $this->db = $database;
  }

  /**
   *
   */
  public function save(array $values) {
    // If there's no vid, insert.
    if (NULL === $values['vid']) {
      $result = $this->db->insert(VariationStorage::BASE_TABLE_NAME)->fields([
        'tid' => $values['tid'],
        'url' => $values['url'],
        'abjs_teid' => $values['abjs_teid'],
      ])->execute();

      return $result;
    }

    $this->db->update(VariationStorage::BASE_TABLE_NAME)->fields([
      'tid' => $values['tid'],
      'url' => $values['url'],
      'abjs_teid' => $values['abjs_teid'],
    ])->condition('vid', $values['vid'])
      ->execute();

    return $values['vid'];
  }

  /**
   *
   */
  public function delete($vid) {
    $query = $this->db->delete(VariationStorage::BASE_TABLE_NAME);
    $query->condition('vid', $vid);
    $query->execute();
  }

}
