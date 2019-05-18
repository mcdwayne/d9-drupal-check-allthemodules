<?php

namespace Drupal\pager;

use Drupal\Core\Database\Connection;

/**
 * Defines the pager storage service.
 */
class PagerStorage {
  protected $dbh;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Database\Connection $dbh
   *   The database handle.
   */
  public function __construct(Connection $dbh) {
    $this->dbh = $dbh;
  }

  /**
   * Fetch an array of content types.
   *
   * @return array
   *   An array of content types.
   */
  public function selectContentTypes() {
    return $this->dbh->query("SELECT DISTINCT(type), type FROM {node} ORDER BY type ASC")->fetchAllKeyed();
  }

  /**
   * Fetch an array of term data.
   *
   * @return array
   *   An array of term data.
   */
  public function selectImgData() {
    return $this->dbh->query("SELECT data FROM {config} WHERE name LIKE 'field.field.node.%.%'")->fetchCol();
  }

  /**
   * Select the next node ID.
   *
   * @param int $created
   *   The node creation time.
   * @param array $tids
   *   The term IDs.
   * @param array $types
   *   The content types.
   *
   * @return int
   *   The ID of the next node.
   */
  public function selectFirst($created, array $tids, array $types) {
    $stmt = "
      SELECT
        n.nid
      FROM
        {node_field_data} n
        JOIN {taxonomy_index} t ON t.nid = n.nid
      WHERE
        n.created < :created
        AND t.tid IN (:tids[])
        AND n.type IN (:types[])
        AND n.status = 1
      ORDER BY
        n.created ASC
    ";
    $args = [
      ':created' => $created,
      ':tids[]'  => $tids,
      ':types[]' => $types,
    ];
    return (int) $this->dbh->queryRange($stmt, 0, 1, $args)->fetchField();
  }

  /**
   * Select the next node ID.
   *
   * @param int $created
   *   The node creation time.
   * @param array $tids
   *   The term IDs.
   * @param array $types
   *   The content types.
   *
   * @return int
   *   The ID of the next node.
   */
  public function selectLast($created, array $tids, array $types) {
    $stmt = "
      SELECT
        n.nid
      FROM
        {node_field_data} n
        JOIN {taxonomy_index} t ON t.nid = n.nid
      WHERE
        n.created > :created
        AND t.tid IN (:tids[])
        AND n.type IN (:types[])
        AND n.status = 1
      ORDER BY
        n.created DESC
    ";
    $args = [
      ':created' => $created,
      ':tids[]'  => $tids,
      ':types[]' => $types,
    ];
    return (int) $this->dbh->queryRange($stmt, 0, 1, $args)->fetchField();
  }

  /**
   * Select the next node ID.
   *
   * @param int $created
   *   The node creation time.
   * @param array $tids
   *   The term IDs.
   * @param array $types
   *   The content types.
   *
   * @return int
   *   The ID of the next node.
   */
  public function selectNext($created, array $tids, array $types) {
    $stmt = "
      SELECT
        n.nid
      FROM
        {node_field_data} n
        JOIN {taxonomy_index} t ON t.nid = n.nid
      WHERE
        n.created > :created
        AND t.tid IN (:tids[])
        AND n.type IN (:types[])
        AND n.status = 1
      ORDER BY
        n.created ASC
    ";
    $args = [
      ':created' => $created,
      ':tids[]'  => $tids,
      ':types[]' => $types,
    ];
    return (int) $this->dbh->queryRange($stmt, 0, 1, $args)->fetchField();
  }

  /**
   * Select the previous node ID.
   *
   * @param int $created
   *   The node creation time.
   * @param array $tids
   *   The term IDs.
   * @param array $types
   *   The content types.
   *
   * @return int
   *   The ID of the previous node.
   */
  public function selectPrev($created, array $tids, array $types) {
    $stmt = "
      SELECT
        n.nid
      FROM
        {node_field_data} n
        JOIN {taxonomy_index} t ON t.nid = n.nid
      WHERE
        n.created < :created
        AND t.tid IN (:tids[])
        AND n.type IN (:types[])
        AND n.status = 1
      ORDER BY
        n.created DESC
    ";
    $args = [
      ':created' => $created,
      ':tids[]'  => $tids,
      ':types[]' => $types,
    ];
    return (int) $this->dbh->queryRange($stmt, 0, 1, $args)->fetchField();
  }

  /**
   * Fetch an array of term data.
   *
   * @return array
   *   An array of term data.
   */
  public function selectTerms() {
    return $this->dbh->query("SELECT vid, tid, name FROM {taxonomy_term_field_data} ORDER BY vid ASC, name ASC")->fetchAll();
  }

  /**
   * Select the previous node ID.
   *
   * @param int $nid
   *   The node ID.
   * @param array $tids
   *   An array of term IDs.
   *
   * @return int
   *   The node's term ID.
   */
  public function selectTid($nid, array $tids) {
    $args = [
      ':nid'    => $nid,
      ':tids[]' => $tids,
    ];
    return (int) $this->dbh->queryRange("SELECT tid FROM {taxonomy_index} WHERE nid = :nid AND tid IN (:tids[])", 0, 1, $args)->fetchField();
  }

}
