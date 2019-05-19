<?php

namespace Drupal\webform_quiz\Model;

/**
 * Represents a data model for a webform submission's quiz results.
 */
class WebformQuizResults {

  /**
   * @var integer
   */
  protected $numberOfPointsReceived;

  /**
   * @var integer
   */
  protected $totalNumberOfPoints;

  /**
   * @var double
   */
  protected $score;

  /**
   * QuizResults constructor.
   *
   * @param int $numberOfPointsReceived
   * @param int $totalNumberOfPoints
   * @param float $score
   */
  public function __construct($numberOfPointsReceived, $totalNumberOfPoints, $score) {
    $this->numberOfPointsReceived = $numberOfPointsReceived;
    $this->totalNumberOfPoints = $totalNumberOfPoints;
    $this->score = $score;
  }

  public static function create($data) {
    return new static(
      $data['webform_quiz_number_of_points_received'],
      $data['webform_quiz_total_number_of_points'],
      $data['webform_quiz_score']
    );
  }

  /**
   * @return int
   */
  public function getNumberOfPointsReceived() {
    return $this->numberOfPointsReceived;
  }

  /**
   * @return int
   */
  public function getTotalNumberOfPoints() {
    return $this->totalNumberOfPoints;
  }

  /**
   * @return float
   */
  public function getScore() {
    return $this->score;
  }

  public function toArray() {
    return [
      'webform_quiz_number_of_points_received' => $this->getNumberOfPointsReceived(),
      'webform_quiz_total_number_of_points' => $this->getTotalNumberOfPoints(),
      'webform_quiz_score' => $this->getScore()
    ];
  }

}
