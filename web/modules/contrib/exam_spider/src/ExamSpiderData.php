<?php

namespace Drupal\exam_spider;

use Drupal\Core\Database\Connection;

/**
 * Defines the ExamSpider service.
 */
class ExamSpiderData implements ExamSpiderDataInterface {

  /**
   * The database connection to use.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Construct the ExamSpider.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection to use.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */

  /**
   * Get exam list using exam id and without exam id complete exam list.
   */
  public function examSpiderGetExam($exam_id = NULL) {
    if (is_numeric($exam_id)) {
      $query = $this->connection->select("exam_list", "el")
        ->fields("el")
        ->condition('id', $exam_id);
      $query = $query->execute();
      return $query->fetchAssoc();
    }
    else {
      $query = $this->connection->select("exam_list", "el")
        ->fields("el");
      $query = $query->execute();
      return $query->fetchAll();
    }
  }

  /**
   * Get questions using question id and without question id questions list.
   */
  public function examSpiderGetQuestion($question_id = NULL) {
    if (is_numeric($question_id)) {
      $query = $this->connection->select("exam_questions", "eq")
        ->fields("eq")
        ->condition('id', $question_id);
      $query = $query->execute();
      return $query->fetchAssoc();
    }
    else {
      $query = $this->connectionexamSpiderGetQuestion->select("exam_questions", "eq")
        ->fields("eq");
      $query = $query->execute();
      return $query->fetchAll();
    }
  }

  /**
   * Get any user last result for any exam.
   */
  public function examSpiderAnyExamLastResult($uid, $exam_id = NULL) {
    if (is_numeric($exam_id)) {
      $query = $this->connection->select("exam_results", "er")
        ->fields("er")
        ->condition('examid', $exam_id)
        ->orderBy('id', 'DESC')
        ->condition('uid', $uid);
      $query = $query->execute();
      return $query->fetchAssoc();
    }
    else {
      return FALSE;
    }
  }

  /**
   * Get time limit function.
   */
  public function examSpidergetTimeLimit($exam_duration) {
    $timer = time() + intval($exam_duration * 60);
    return date('r', $timer);
  }

}
