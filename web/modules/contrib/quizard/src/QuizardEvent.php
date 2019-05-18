<?php

/**
 * @file
 * Contains Drupal\quizard\QuizardEvent.
 */

namespace Drupal\quizard;

use Symfony\Component\EventDispatcher\Event;

class QuizardEvent extends Event {

  protected $quiz_event;

  /**
   * Constructor.
   *
   * @param $quiz_event
   */
  public function __construct($quiz_event) {
    $this->quiz_event = $quiz_event;
  }

  /**
   * Getter for the quiz_event object.
   *
   * @return $quiz_event
   */
  public function getEvent() {
    return $this->quiz_event;
  }

  /**
   * Setter for the quiz_event object.
   *
   * @param $quiz_event
   */
  public function setEvent($quiz_event) {
    $this->quiz_event = $quiz_event;
  }

}