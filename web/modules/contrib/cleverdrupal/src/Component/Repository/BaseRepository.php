<?php

namespace Drupal\cleverreach\Component\Repository;

use Drupal;
use PDO;

/**
 * Base repository class for custom entity types.
 */
class BaseRepository {
  const TABLE_NAME = '';
  const TABLE_PK = 'id';

  /**
   * Finds record by primary key.
   *
   * @param int $id
   *
   * @return array|null
   */
  public function findById($id) {
    return $this->findOne([static::TABLE_PK => $id]);
  }

  /**
   * Finds one record by provided conditions.
   *
   * @param array $filterBy
   *   List of simple search filters as key-value pair. Leave empty for unfiltered result.
   * @param array $sortBy
   *   List of sorting options where key is field and value is sort direction
   *   ("ASC" or "DESC"). Leave empty for default sorting.
   *
   * @return array
   */
  public function findOne($filterBy = NULL, $sortBy = NULL) {
    $item = $this->findAll($filterBy, $sortBy, 0, 1);
    return !empty($item) ? reset($item) : NULL;
  }

  /**
   * Finds all records for provided conditions ordered in provided sort.
   *
   * @param array $filterBy
   *   List of simple search filters as key-value pair. Leave empty for unfiltered result.
   * @param array $sortBy
   *   List of sorting options where key is field and value is sort direction
   *   ("ASC" or "DESC"). Leave empty for default sorting.
   * @param int $start
   *   From which record index result set should start.
   * @param int $limit
   *   Max number of records that should be returned (default is 10)
   * @param array $select
   *   List of table columns to return. Column names could have alias as well.
   *   If empty, all columns are returned.
   *
   * @return array
   */
  public function findAll($filterBy = NULL, $sortBy = NULL, $start = 0, $limit = 0, $select = NULL) {
    $query = Drupal::database()->select(static::TABLE_NAME);
    $query->fields(static::TABLE_NAME, $select === NULL ? [] : $select);

    if (is_array($filterBy)) {
      foreach ($filterBy as $field => $value) {
        $query->condition($this->toUnderscoreCase($field), $value, $value === NULL ? 'IS NULL' : '=');
      }
    }

    if (is_array($sortBy)) {
      foreach ($sortBy as $sortField => $direction) {
        $query->orderBy($this->toUnderscoreCase($sortField), $direction);
      }
    }

    if ($limit > 0) {
      $query->range($start, $limit);
    }

    if (!$execute = $query->execute()) {
      return NULL;
    }

    return $execute->fetchAllAssoc(static::TABLE_PK, PDO::FETCH_ASSOC);
  }

  /**
   * @param array $fields
   *
   * @return \Drupal\Core\Database\StatementInterface|int|null
   * @throws \Exception
   */
  public function insert($fields) {
    $database = Drupal::database()->insert(static::TABLE_NAME)->fields($fields);
    return $database->execute();
  }

  /**
   * Updates row in database by provided conditions.
   *
   * @param array $fields
   * @param array $conditions
   *
   * @return \Drupal\Core\Database\StatementInterface|int|null
   */
  public function update($fields, $conditions) {
    $database = \Drupal::database()->update(static::TABLE_NAME)->fields($fields);

    foreach ($conditions as $field => $value) {
      $database->condition($this->toUnderscoreCase($field), $value, $value === NULL ? 'IS NULL' : '=');
    }

    return $database->execute();
  }

  /**
   * Deletes row by ID.
   *
   * @param int $id
   */
  public function deleteById($id) {
    Drupal::database()->delete(static::TABLE_NAME)->condition(static::TABLE_PK, $id)->execute();
  }

  /**
   * Helper method that performs converting of camel case to underscore case.
   *
   * @param string $input
   *   String to be converted to underscore case.
   *
   * @return string
   *   Camelcase string representation.
   */
  private function toUnderscoreCase($input) {
    preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
    $ret = $matches[0];
    foreach ($ret as &$match) {
      $match = $match === strtoupper($match) ? strtolower($match) : lcfirst($match);
    }
    return implode('_', $ret);
  }

}
