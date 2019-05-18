<?php

namespace Drupal\Driver\Database\oracle;

/**
 * Handle long identifiers for Oracle database.
 */
class LongIdentifierHandler {

  /**
   * Holds search reg exp pattern to match known long identifiers.
   *
   * @var array
   */
  private $searchLongIdentifiers = array();

  /**
   * Holds replacement string to replace known long identifiers.
   *
   * @var array
   */
  private $replaceLongIdentifiers = array();

  /**
   * Holds long identifier hash map.
   *
   * @var array
   */
  private $hashLongIdentifiers = array();

  /**
   * The parent connection.
   *
   * @var \Drupal\Driver\Database\oracle\Connection
   */
  private $connection;

  /**
   * {@inheritdoc}
   */
  public function __construct($connection) {
    $this->connection = $connection;

    // Load long identifiers for the first time in this connection.
    $this->resetLongIdentifiers();
  }

  /**
   * {@inheritdoc}
   */
  public function escapeLongIdentifiers($query) {
    $ret = '';

    // Do not replace things in literals.
    $literals = array();
    preg_match_all("/'.*?'/", $query, $literals);
    $literals    = $literals[0];
    $replaceable = preg_split("/'.*?'/", $query);
    $lidx        = 0;

    // Assume that a query cannot start with a literal and that.
    foreach ($replaceable as $toescape) {
      $ret .= $this->removeLongIdentifiers($toescape) . (isset($literals[$lidx]) ? $literals[$lidx++] : "");
    }
    return $ret;
  }

  /**
   * {@inheritdoc}
   */
  public function removeLongIdentifiers($query_part) {
    if (count($this->searchLongIdentifiers)) {
      return preg_replace($this->searchLongIdentifiers, $this->replaceLongIdentifiers, $query_part);
    }
    else {
      return $query_part;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetLongIdentifiers() {
    // @TODO: would be wonderful to enable a memcached switch here.
    try {
      $result = $this->connection->oracleQuery("select id, identifier from long_identifiers where substr(identifier,1,3) not in ('IDX','TRG','PK_','UK_') order by length(identifier) desc");

      while ($row = $result->fetchObject()) {
        $this->searchLongIdentifiers[] = '/\b' . $row->identifier . '\b/i';
        $this->replaceLongIdentifiers[] = ORACLE_LONG_IDENTIFIER_PREFIX . $row->id;
        $this->hashLongIdentifiers[ORACLE_LONG_IDENTIFIER_PREFIX . $row->id] = strtolower($row->identifier);
      }
    }
    catch (\Exception $e) {
      // Ignore until long_identifiers table is not created.
    }
  }

  /**
   * {@inheritdoc}
   */
  public function findAndRecordLongIdentifiers($query_part) {
    preg_match_all("/\w+/", $query_part, $words);
    $words = $words[0];
    foreach ($words as $word) {
      if (strlen($word) > ORACLE_IDENTIFIER_MAX_LENGTH) {
        $this->connection->schema()->oid($word);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function findAndRemoveLongIdentifiers($query) {
    $this->connection->removeFromCachedStatements($query);

    // Do not replace things in literals.
    $replaceable = preg_split("/'.*?'/", $query);

    // Assume that a query cannot start with a literal and that.
    foreach ($replaceable as $toescape) {
      $this->findAndRecordLongIdentifiers($toescape);
    }
    $this->resetLongIdentifiers();
  }

  /**
   * {@inheritdoc}
   */
  public function longIdentifierKey($key) {
    return $this->hashLongIdentifiers[strtoupper($key)];
  }

  /**
   * {@inheritdoc}
   */
  public function serialize() {
    return serialize(array());
  }

}
