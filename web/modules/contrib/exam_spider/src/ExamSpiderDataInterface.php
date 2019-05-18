<?php

namespace Drupal\exam_spider;

/**
 * Provides an interface defining a ExamSoider data.
 */
interface ExamSpiderDataInterface {

  /**
   * Returns data stored for a Exam.
   *
   * @param int $exam_id
   *   The Exam ID the data is associated with Exam.
   */
  public function examSpiderGetExam($exam_id = NULL);

  /**
   * Returns data stored for a Question.
   *
   * @param int $question_id
   *   The Question ID the data is associated with Question.
   */
  public function examSpiderGetQuestion($question_id = NULL);

  /**
   * Returns data stored for a Exam result.
   *
   * @param int $uid
   *   The User ID the data is associated with user.
   * @param int $exam_id
   *   The Exam ID the data is associated with Exam.
   */
  public function examSpiderAnyExamLastResult($uid, $exam_id = NULL);

  /**
   * Returns time limit for a Exam.
   *
   * @param int $exam_duration
   *   The Exam duration the time is associated with Exam.
   */
  public function examSpidergetTimeLimit($exam_duration);

}
