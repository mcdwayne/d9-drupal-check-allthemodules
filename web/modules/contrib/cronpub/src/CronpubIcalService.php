<?php

/**
 * @file
 * Contains \Drupal\cronpub\CronpubExecutionService.
 */

namespace Drupal\cronpub;

use Drupal\Core\Datetime\DrupalDateTime;
use \Recurr\RecurrenceCollection;
use \Recurr\Rule;
use \Recurr\Transformer\ArrayTransformer;

// We can use this library later on for calendar overview.
// use \Eluceo\iCal\Component\Calendar;
/**
 * Class CronpubIcalService interprets the RRULE and returns array with dates.
 *
 * @package Drupal\cronpub
 */
class CronpubIcalService {

  /**
   * Defines the maximum number of resulted events from RRule.
   */
  const MAX_COUNT = 250;

  /**
   * The iCallibrary.
   *
   * @var object
   *    The library generating the ICAL items.
   */
  protected $ical;

  /**
   * @var \DateTimeZone
   */
  private $timezone;

  /**
   * Sequence start date.
   *
   * @var \DateTime
   */
  private $start;

  /**
   * Sequence end date.
   *
   * @var \DateTime
   */
  private $end;

  /**
   * Array containing the calculated dates from the recursion rule.
   *
   * @var array
   *   Follows schema of key: UNIX timestamp, val: array of parameters.
   */
  private $calcDates;

  /**
   * @var string
   *
   * The cronpub plugin to use for execution.
   */
  protected $plugin;

  /**
   * The Rules given to the computing object.
   *
   * @var array
   *   The collection of parameters.
   */
  protected $rruleArray = [];
  protected $rrule;

  /**
   * Set the parameter from form field to generate dates.
   *
   * @param array $params
   *   The field value from form.
   */
  public function __construct(array $params) {
    $this->setTimezone($params['start']);
    $this->setStart($params['start']);
    $this->setEnd($params['end']);
    $this->setRrule($params['rrule']);
  }

  /**
   * Set the dates timezone.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *   The start value.
   */
  public function setTimezone(DrupalDateTime $start) {
    if ($start) {
      $this->timezone = new \DateTimeZone(
        // DrupalDateTime supports method getTimezone.
        $start->getTimezone()->getName()
      );
    }
    else {
      $this->timezone = new \DateTimeZone('UTC');
    }
  }

  /**
   * Set the start date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $start
   *   The start value.
   */
  public function setStart(DrupalDateTime $start) {
    if ($start) {
      $this->start = new \DateTime(
        $start->format('Y-m-d H:i:s'),
        $this->timezone
      );
    }
    else {
      $this->start = NULL;
    }
  }

  /**
   * Set the end date.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $end
   *   The start value.
   */
  public function setEnd(DrupalDateTime $end) {
    if ($end) {
      $this->end = new \DateTime(
        $end->format('Y-m-d H:i:s'),
        $this->timezone
      );
    }
    else {
      $this->end = NULL;
    }
  }

  /**
   * Set the action plugin to use.
   *
   * @param string $plugin
   *   The start value.
   */
  public function setPlugin($plugin) {
    $this->plugin = $plugin;
  }

  /**
   * Generate formatted output to $this->calcDates from simshaun/recurr.
   *
   * @param \Recurr\RecurrenceCollection $dates
   *   The doctrine output from library.
   */
  private function setCalcDates(RecurrenceCollection $dates) {
    $this->calcDates = [];
    foreach ($dates as $date) {
      $start_timestamp = $date->getStart()->format('U');
      $this->calcDates[$start_timestamp] = [
        'job' => 'start',
        'state' => 'pending',
        'verified' => TRUE,
      ];
      $end_timestamp = $date->getEnd()->format('U');
      $this->calcDates[$end_timestamp] = [
        'job' => 'end',
        'state' => 'pending',
        'verified' => TRUE,
      ];
    }
  }


  /**
   * Part using library simshaun/recurr to generate all dates from RRULE string.
   *
   * @return array|NULL
   *   The formatted data for conf entity.
   */
  public function getDates() {
    try {
      $rule = new Rule(
        $this->rrule,
        $this->start,
        $this->end,
        $this->timezone->getName()
      );
      $transformer = new ArrayTransformer();
      $dates = $transformer->transform($rule);
      $this->setCalcDates($dates);
    }
    catch (\Exception $e) {
      // Something went wrong when building RULE or transforming data in Cronpub.
      watchdog_exception('cronpub', $e);
    }
    return $this->calcDates;
  }

  /**
   * Create RRULE array and string from class data.
   *
   * @param string $rrule
   */
  public function setRrule($rrule) {
    // @toDo check and correct typical rrule errors, caused by library or humans.
    $this->rrule = (string) $rrule;
  }

}
