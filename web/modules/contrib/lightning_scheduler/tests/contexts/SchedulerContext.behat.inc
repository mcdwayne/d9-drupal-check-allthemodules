<?php

namespace Acquia\LightningExtension\Context;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;

/**
 * Contains step definitions for interacting with the Lightning Scheduler UI.
 */
final class SchedulerContext extends DrupalSubContextBase {

  /**
   * @BeforeScenario
   */
  public function setUp() {
    ini_set('date.timezone', drupal_get_user_timezone());
  }

  /**
   * Prepares a scheduled transition, without saving it.
   *
   * @param string $state
   *   The destination state of the transition.
   * @param string $date
   *   The date of the transition in a format that can be parsed by strtotime().
   * @param string $time
   *   The time of the transition in a format that can be parsed by strtotime().
   *
   * @When I prepare a transition to :state on :date at :time
   */
  public function prepare($state, $date, $time) {
    $assert = $this->assertSession();

    try {
      $assert->elementExists('named', ['link', 'add another'])->click();
    }
    catch (ElementNotFoundException $e) {
      $assert->elementExists('named', ['link', 'Schedule a status change'])->click();
    }

    $assert->fieldExists('Scheduled moderation state')->selectOption($state);
    $this->setValue('Scheduled transition date', date('Y-m-d', strtotime($date)));
    $this->setValue('Scheduled transition time', date('H:i:s', strtotime($time)));
  }

  /**
   * Sets the value of a field and tries to trigger a React event on it.
   *
   * @param string $field
   *   The field locator.
   * @param mixed $value
   *   The value to set.
   * @param string $event
   *   (optional) The event to trigger. Defaults to 'change'.
   */
  private function setValue($field, $value, $event = 'change') {
    $field = $this->assertSession()->fieldExists($field);
    $field->setValue($value);

    $field = $field->getXpath();

    // If React's testing utilities are present, simulate the event so that all
    // values will be updated successfully.
    $js = <<<END
if (ReactTestUtils) {
  let element = document.evaluate('$field', document, null).iterateNext();
  ReactTestUtils.Simulate.$event(element);
}
END;
    $this->getSession()->executeScript($js);
  }

  /**
   * Schedules a moderation state transition.
   *
   * @param string $state
   *   The destination state of the transition.
   * @param string $date
   *   The date of the transition in YYYY-MM-DD format.
   * @param string $time
   *   The time of the transition in HH:MM:SS format.
   *
   * @When I schedule a transition to :state on :date at :time
   */
  public function add($state, $date, $time) {
    $this->prepare($state, $date, $time);

    $this->assertSession()
      ->elementExists('named', ['button', 'Save transition'])
      ->click();
  }

  /**
   * Schedules a moderation state transition from a time stamp.
   *
   * @param string $state
   *   The destination state of the transition.
   * @param int $ts
   *   The time stamp when the transition should take place.
   */
  private function addFromTimeStamp($state, $ts) {
    return $this->add($state, date('Y-m-d', $ts), date('H:i:s', $ts));
  }

  /**
   * Schedules a transition to take place a certain number of seconds ago.
   *
   * @param string $state
   *   The destination state of the transition.
   * @param int $n
   *   The number of seconds.
   *
   * @When I schedule a transition to :state :n second(s) ago
   */
  public function addInSecondsAgo($state, $n) {
    $this->addFromTimeStamp($state, time() - $n);
  }

  /**
   * Schedules a transition to take place a certain number of minutes ago.
   *
   * @param string $state
   *   The destination state of the transition.
   * @param int $n
   *   The number of minutes.
   *
   * @When I schedule a transition to :state :n minute(s) ago
   */
  public function addInMinutesAgo($state, $n) {
    $this->addInSecondsAgo($state, $n * 60);
  }

  /**
   * Schedules a transition to take place a certain number of hours ago.
   *
   * @param string $state
   *   The destination state of the transition.
   * @param int $n
   *   The number of hours.
   *
   * @When I schedule a transition to :state :n hour(s) ago
   */
  public function addInHoursAgo($state, $n) {
    $this->addInMinutesAgo($state, $n * 60);
  }

  /**
   * Schedules a transition to take place a certain number of days ago.
   *
   * @param string $state
   *   The destination state of the transition.
   * @param int $n
   *   The number of days.
   *
   * @When I schedule a transition to :state :n day(s) ago
   */
  public function addInDaysAgo($state, $n) {
    $this->addInHoursAgo($state, $n * 24);
  }

  /**
   * Schedules a transition to take place a certain number of seconds from now.
   *
   * @param string $state
   *   The destination state of the transition.
   * @param int $n
   *   The number of seconds.
   *
   * @When I schedule a transition to :state in :n second(s)
   */
  public function addInSecondsFromNow($state, $n) {
    $this->addFromTimeStamp($state, time() + $n);
  }

  /**
   * Schedules a transition to take place a certain number of minutes from now.
   *
   * @param string $state
   *   The destination state of the transition.
   * @param int $n
   *   The number of minutes.
   *
   * @When I schedule a transition to :state in :n minute(s)
   */
  public function addInMinutesFromNow($state, $n) {
    $this->addInSecondsFromNow($state, $n * 60);
  }

  /**
   * Schedules a transition to take place a certain number of hours from now.
   *
   * @param string $state
   *   The destination state of the transition.
   * @param int $n
   *   The number of hours.
   *
   * @When I schedule a transition to :state in :n hour(s)
   */
  public function addInHoursFromNow($state, $n) {
    $this->addInMinutesFromNow($state, $n * 60);
  }

  /**
   * Schedules a transition to take place a certain number of days from now.
   *
   * @param string $state
   *   The destination state of the transition.
   * @param int $n
   *   The number of days.
   *
   * @When I schedule a transition to :state in :n day(s)
   */
  public function addInDaysFromNow($state, $n) {
    $this->addInHoursFromNow($state, $n * 24);
  }

}
