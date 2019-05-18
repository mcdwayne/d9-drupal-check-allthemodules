<?php

namespace Drupal\question_field;

/**
 * Class AnswerOptions.
 */
class AnswerOptions {

  /**
   * The answer option.
   *
   * @var string
   */
  protected $value;

  /**
   * The textual representation of the answer option.
   *
   * @var string
   */
  protected $text;

  /**
   * The followup question ids.
   *
   * @var array
   */
  protected $followups;

  /**
   * AnswerOptions constructor.
   *
   * @param string $options
   *   The | delimited options.
   */
  public function __construct($options) {
    list($this->value, $this->text, $followups) = explode('|', "$options|", 4);
    $this->followups = $followups ? explode('+', $followups) : [];
  }

  /**
   * Return the answer option.
   *
   * @return string
   *   The answer option.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Return the textual representation of the answer option.
   *
   * @return string
   *   The textual representation of the answer option.
   */
  public function getText() {
    return $this->text;
  }

  /**
   * Return an array of the followup question ids.
   *
   * @return array
   *   The followup question ids.
   */
  public function getFollowups() {
    return $this->followups;
  }

}
