<?php

namespace Drupal\feedbacks\Form;

use Drupal\Core\Database\Database;

/**
 * CRUD functions for Feedbacks.
 */
class FeedbackClass {

  /**
   * Add Function (inserting new feedbac)
   *
   * @param string $name
   *   Name of submitter.
   * @param string $message
   *   Feedback message.
   * @param string $path
   *   Page url where feedback given.
   * @param int $timestamp
   *   Current Timestamp.
   *
   * @throws \Exception
   */
  public static function add($name, $message, $path, $timestamp) {
    $connection = Database::getConnection();
    $connection->insert('feedbacks')->fields(
      [
        'name' => $name,
        'message' => $message,
        'path' => $path,
        'timestamp' => $timestamp,
      ]
    )->execute();
  }

  /**
   * GetAll Function.
   *
   * @return array
   *   Returns associative array of all Feedbacks.
   */
  public static function getAll($header) {
    $connection = Database::getConnection();
    $sth = $connection->select('feedbacks', 'x')
      ->fields('x',
        ['id', 'name', 'message', 'path', 'status', 'timestamp']
      );
    $table_sort = $sth->extend('Drupal\Core\Database\Query\TableSortExtender')->orderByHeader($header);
    $executed = $table_sort->execute();
    $results = $executed->fetchAllAssoc('id', \PDO::FETCH_OBJ);
    return $results;
  }

  /**
   * Single feedback function.
   *
   * @param int $id
   *   Id of feedback.
   *
   * @return array
   *   Returns associative array of Single Feedback.
   */
  public static function getOne($id) {
    $connection = Database::getConnection();
    $single = $connection->select('feedbacks', 'x')
      ->fields('x', ['id', 'name', 'message', 'path', 'status'])
      ->condition('id', $id);
    $executed = $single->execute();
    $result = $executed->fetchAssoc();
    return $result;
  }

  /**
   * Check if Feedback Exists.
   *
   * @return bool
   *   Returns if Feedback exists or not
   */
  public static function exists($id) {
    $connection = Database::getConnection();
    $exists = $connection->select('feedbacks', 'x')
      ->fields('x', ['id'])
      ->condition('id', $id)
      ->countQuery()
      ->execute()
      ->fetchField();
    return $exists;
  }

  /**
   * Update Function (updating feedback)
   *
   * @return int
   *   Returns id of the updated feedback.
   *
   * @throws \Exception
   */
  public static function update($status, $message, $path, $id) {
    $connection = Database::getConnection();
    $updated = $connection->update('feedbacks')
      ->fields(
        [
          'status' => $status,
          'message' => $message,
          'path' => $path,
        ]
      )
      ->condition('id', $id)
      ->execute();
    return $updated;
  }

  /**
   * Delete function.
   *
   * @param int $id
   *   Id of feedback to be deleted.
   */
  public static function delete($id) {
    $connection = Database::getConnection();
    $connection->delete('feedbacks')
      ->condition('id', $id)
      ->execute();
  }

}
