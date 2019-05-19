<?php

namespace Drupal\webform_quiz\Helper;


use Drupal\webform\Entity\WebformSubmission;

abstract class CalculatorBase {

  /**
   * @var \Drupal\webform\Entity\WebformSubmission
   */
  protected $webformSubmission;

  /**
   * @var mixed
   */
  protected $results;

  /**
   * ScoreCalculator constructor.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $webformSubmission
   */
  public function __construct(WebformSubmission $webformSubmission) {
    $this->webformSubmission = $webformSubmission;
    $this->calculate();
  }

  protected abstract function calculate();

  /**
   * @return mixed
   */
  public function getResults() {
    return $this->results;
  }

}
