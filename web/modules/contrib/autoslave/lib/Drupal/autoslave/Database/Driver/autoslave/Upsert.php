<?php

/**
 * @file
 * Definition of Drupal\autoslave\Database\Driver\autoslave\Upsert
 *
 * This SHOULD probably be rewritten so it uses the appropriate driver Upsert class.
 * To get autoslave working for now I pasted the __toString() function from MySQL since that's what we are using.
 */

namespace Drupal\Core\Database\Driver\autoslave;

use Drupal\Core\Database\Query\Upsert as QueryUpsert;

class Upsert extends QueryUpsert {
  
   /**
   * {@inheritdoc}
   */
  public function __toString() {
    // Create a sanitized comment string to prepend to the query.
    $comments = $this->connection->makeComment($this->comments);

    // Default fields are always placed first for consistency.
    $insert_fields = array_merge($this->defaultFields, $this->insertFields);

    $query = $comments . 'INSERT INTO {' . $this->table . '} (' . implode(', ', $insert_fields) . ') VALUES ';

    $values = $this->getInsertPlaceholderFragment($this->insertValues, $this->defaultFields);
    $query .= implode(', ', $values);

    // Updating the unique / primary key is not necessary.
    unset($insert_fields[$this->key]);

    $update = [];
    foreach ($insert_fields as $field) {
      $update[] = "$field = VALUES($field)";
    }

    $query .= ' ON DUPLICATE KEY UPDATE ' . implode(', ', $update);

    return $query;
  }
  
}
