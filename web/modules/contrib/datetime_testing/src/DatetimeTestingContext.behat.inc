<?php

use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;

/**
 * A Behat subcontext that allows manipulating the time.
 */
class DatetimeTestingContext extends DrupalSubContextBase implements DrupalSubContextInterface {

  /**
   * Set the time using a string understood by PHP strtotime().
   *
   * @Given the time/date is :time
   * @Given the day is :time
   * @When :time of time pass/passes
   * @When :time pass/passes
   */
  public function timeIs($time) {
    $this->getDriver();
    \Drupal::time()->setTime($time);
  }

  /**
   * Set the time, and freeze it at that time.
   *
   * @Given the time/date is frozen as/at :time
   * @Given the day is frozen as/at :time
   */
  public function timeIsFrozenAs($time) {
    $this->getDriver();
    \Drupal::time()->freezeTime();
    \Drupal::time()->setTime($time);
  }

  /**
   * Stop time from passing.
   *
   * @Given time is frozen
   */
  public function timeIsFrozen() {
    $this->getDriver();
    \Drupal::time()->freezeTime();
  }

  /**
   * Allow time to resume passing.
   *
   * @When time is unfrozen
   */
  public function timeIsUnfrozen() {
    $this->getDriver();
    \Drupal::time()->unfreezeTime();
  }

  /**
   * Undo the effect of any time manipulations.
   *
   * @AfterScenario
   */
  public function resetTime(AfterScenarioScope $scope) {
    $this->getDriver();
    \Drupal::time()->resetTime();
  }

}
