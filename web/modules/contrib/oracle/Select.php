<?php

namespace Drupal\Driver\Database\oracle;

use Drupal\Core\Database\Query\Select as QuerySelect;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Oracle implementation of \Drupal\Core\Database\Query\Select.
 */
class Select extends QuerySelect {

  /**
   * {@inheritdoc}
   */
  public function arguments() {
    return array_map(function ($value) {
      return $value === '' ? ORACLE_EMPTY_STRING_REPLACER : $value;
    }, parent::arguments());
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    // Create a sanitized comment string to prepend to the query.
    $comments = $this->connection->makeComment($this->comments);

    // Expanding group by aliases.
    if ($this->group) {
      foreach ($this->group as $key => &$group_field) {
        if (isset($this->fields[$group_field])) {
          $field = $this->fields[$group_field];
          $group_field = (isset($field['table']) ? $field['table'] . '.' : '') . $field['field'];
        }
        elseif (isset($this->expressions[$group_field])) {
          $expression = $this->expressions[$group_field];
          $group_field = $expression['expression'];
        }
      }
    }

    // SELECT.
    $query = $comments . 'SELECT ';
    if ($this->distinct) {
      $query .= 'DISTINCT ';
    }

    // FIELDS and EXPRESSIONS.
    $fields = array();
    foreach ($this->tables as $alias => $table) {
      if (!empty($table['all_fields'])) {
        $fields[] = $alias . '.*';
      }
    }
    foreach ($this->fields as $alias => $field) {
      // Always use the AS keyword for field aliases, as some
      // databases require it (e.g., PostgreSQL).
      $fields[] = (isset($field['table']) ? $field['table'] . '.' : '') . $field['field'] . ' AS ' . $field['alias'];
    }
    foreach ($this->expressions as $alias => $expression) {
      // Check if it isn't comparison expression. If it is so the we need to
      // replace it CASE..WHEN..THEN construction.
      $expression_string = $expression['expression'];
      if (preg_match("/^(.*?)([<>=!]{1,})(.*)$/", $expression['expression'], $matches)) {
        $matches = array_map('trim', $matches);
        $matches = array_filter($matches);
        if (count($matches) == 4) {
          $expression_string = "CASE\nWHEN " . $matches[1] . $matches[2] . $matches[3] . " THEN 1\nELSE 0\nEND\n";
        }
      }

      $fields[] = $expression_string . ' AS ' . $expression['alias'];
    }
    $query .= implode(', ', $fields);

    // FROM - We presume all queries have a FROM, as any query that doesn't
    // won't need the query builder anyway.
    $query .= "\nFROM ";
    foreach ($this->tables as $alias => $table) {
      $query .= "\n";
      if (isset($table['join type'])) {
        $query .= $table['join type'] . ' JOIN ';
      }

      // If the table is a subquery, compile it and integrate it into the query.
      if ($table['table'] instanceof SelectInterface) {
        $table_string = '(' . (string) $table['table'] . ')';
      }
      else {
        $table_string = '{' . $this->connection->escapeTable($table['table']) . '}';
      }

      // Don't use the AS keyword for table aliases, as some
      // databases don't support it (e.g., Oracle).
      $query .= $table_string . ' ' . $table['alias'];

      if (!empty($table['condition'])) {
        $query .= ' ON ' . $table['condition'];
      }
    }

    // WHERE.
    if (count($this->condition)) {
      if (!$this->condition->compiled()) {
        $this->condition->compile($this->connection, $this);
      }

      // There is an implicit string cast on $this->condition.
      $query .= "\nWHERE " . $this->condition;
    }

    // GROUP BY.
    if ($this->group) {
      $query .= "\nGROUP BY " . implode(', ', $this->group);
    }

    // HAVING.
    if (count($this->having)) {
      if (!$this->having->compiled()) {
        $this->having->compile($this->connection, $this);
      }

      // There is an implicit string cast on $this->having.
      $query .= "\nHAVING " . $this->having;
    }

    // ORDER BY.
    if ($this->order) {
      $query .= "\nORDER BY ";
      $fields = array();
      foreach ($this->order as $field => $direction) {
        $fields[] = $field . ' ' . $direction;
      }
      $query .= implode(', ', $fields);
    }

    // UNION is a little odd, as the select queries to combine are passed into
    // this query, but syntactically they all end up on the same level.
    if ($this->union) {
      foreach ($this->union as $union) {
        $query .= ' ' . $union['type'] . ' ' . (string) $union['query'];
      }
    }

    if (!empty($this->range)) {
      $start = ((int) $this->range['start'] + 1);
      $end = ((int) $this->range['length'] + (int) $this->range['start']);

      $query = 'SELECT * FROM (SELECT TAB.*, ROWNUM ' . ORACLE_ROWNUM_ALIAS . ' FROM (' . $query . ') TAB) WHERE ' . ORACLE_ROWNUM_ALIAS . ' BETWEEN ' . $start . ' AND ' . $end;
    }

    return $query;
  }

}
