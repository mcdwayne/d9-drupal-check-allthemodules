<?php

namespace Drupal\moon_phases;

use DateTime;

define('MP_NEW_MOON_NAME', 'New Moon');
define('MP_NEW_MOON_ID', 0);
define('MP_WAXING_CRESCENT_NAME', 'Waxing Crescent');
define('MP_WAXING_CRESCENT_ID', 1);
define('MP_FIRST_QUARTER_NAME', 'First Quarter');
define('MP_FIRST_QUARTER_ID', 2);
define('MP_WAXING_GIBBOUS_NAME', 'Waxing Gibbous');
define('MP_WAXING_GIBBOUS_ID', 3);
define('MP_FULL_MOON_NAME', 'Full Moon');
define('MP_FULL_MOON_ID', 4);
define('MP_WANING_GIBBOUS_NAME', 'Waning Gibbous');
define('MP_WANING_GIBBOUS_ID', 5);
define('MP_THIRD_QUARTER_MOON_NAME', 'Third Quarter');
define('MP_THIRD_QUARTER_MOON_ID', 6);
define('MP_WANING_CRESCENT_NAME', 'Waning Crescent');
define('MP_WANING_CRESCENT_ID', 7);
define('MP_DAY_IN_SECONDS', 86400);

/**
 * {@inheritdoc}
 */
class MoonCalc implements MoonCalcInterface {

  /**
   * An array containing the moon phase name and ID.
   *
   * @var array
   */
  protected $allPhaseData;

  /**
   * The date for which to calculate the moon phase.
   *
   * @var \DateTime
   */
  protected $moonPhaseDate;

  /**
   * The date for which to calculate the moon phase as a timestamp.
   *
   * @var int
   */
  protected $dateAsTimeStamp;

  /**
   * The numerical ID for the moon phase.
   *
   * @var int
   */
  protected $moonPhaseIdForDate;

  /**
   * The string representing the moon phase name.
   *
   * @var string
   */
  protected $moonPhaseNameForDate;

  /**
   * Set the number of days in a moon phase.
   *
   * @var float
   */
  protected $periodInDays;

  /**
   * The percentage of illumination for the moon phase.
   *
   * @var float
   */
  protected $percentOfIllumination;

  /**
   * The number of days until the next full moon.
   *
   * @var int
   */
  protected $daysUntilNextFullMoon;

  /**
   * The number of days until the next last quarter moon.
   *
   * @var int
   */
  protected $daysUntilNextLastQuarterMoon;

  /**
   * The number of days until the next first quarter moon.
   *
   * @var int
   */
  protected $daysUntilNextFirstQuarterMoon;

  /**
   * The number of days until the next new moon.
   *
   * @var int
   */
  protected $daysUntilNextNewMoon;

  /**
   * The position, between 0 and 1, of the moon phase.
   *
   * @var float
   */
  protected $positionInCycle;

  /**
   * The period in seconds.
   *
   * @var int
   */
  protected $periodInSeconds;

  /**
   * The date of a known full moon.
   *
   * @var \DateTime
   */
  protected $baseFullMoonDate;

  /**
   * MoonCalc constructor.
   *
   * @param \DateTime $date
   *   The date as DateTime.
   */
  public function __construct(DateTime $date) {
    $this->setAllPhaseData();
    $this->setBaseFullMoonDate();
    $this->setMoonPhaseDate($date);
    $this->setPeriodInDays();
    $this->setPeriodInSeconds();
    $this->setPositionInCycle();
    $this->calcMoonPhase();
    $this->setDaysUntilNextFirstQuarterMoon();
    $this->setDaysUntilNextFullMoon();
    $this->setDaysUntilNextLastQuarterMoon();
    $this->setDaysUntilNextNewMoon();
    $this->setPercentOfIllumination();
  }

  /**
   * Sets the moon phase ID and moon phase name.
   */
  private function calcMoonPhase() {
    $position = $this->getPositionInCycle();

    if ($position >= 0.474 && $position <= 0.53) {
      $phaseInfoForCurrentDate = [MP_NEW_MOON_ID, MP_NEW_MOON_NAME];
    }
    elseif ($position >= 0.54 && $position <= 0.724) {
      $phaseInfoForCurrentDate = [MP_WAXING_CRESCENT_ID, MP_WAXING_CRESCENT_NAME];
    }
    elseif ($position >= 0.725 && $position <= 0.776) {
      $phaseInfoForCurrentDate = [MP_FIRST_QUARTER_ID, MP_FIRST_QUARTER_NAME];
    }
    elseif ($position >= 0.777 && $position <= 0.974) {
      $phaseInfoForCurrentDate = [MP_WAXING_GIBBOUS_ID, MP_WAXING_GIBBOUS_NAME];
    }
    elseif ($position >= 0.975 || $position <= 0.026) {
      $phaseInfoForCurrentDate = [MP_FULL_MOON_ID, MP_FULL_MOON_NAME];
    }
    elseif ($position >= 0.027 && $position <= 0.234) {
      $phaseInfoForCurrentDate = [MP_WANING_GIBBOUS_ID, MP_WANING_GIBBOUS_NAME];
    }
    elseif ($position >= 0.235 && $position <= 0.295) {
      $phaseInfoForCurrentDate = [MP_THIRD_QUARTER_MOON_ID, MP_THIRD_QUARTER_MOON_NAME];
    }
    else {
      $phaseInfoForCurrentDate = [MP_WANING_CRESCENT_ID, MP_WANING_CRESCENT_NAME];
    }

    list($this->moonPhaseIdForDate, $this->moonPhaseNameForDate) = $phaseInfoForCurrentDate;
  }

  /**
   * {@inheritdoc}
   */
  public function getMoonPhaseId() {
    return $this->moonPhaseIdForDate;
  }

  /**
   * {@inheritdoc}
   */
  public function getMoonPhaseName() {
    return $this->moonPhaseNameForDate;
  }

  /**
   * Sets the position in the current phase cycle.
   */
  private function setPositionInCycle() {
    $baseDateTimestamp = strtotime($this->baseFullMoonDate->format('Y-m-d H:i:s'));
    $diff = $this->getDateAsTimestamp() - $baseDateTimestamp;

    $periodInSeconds = $this->periodInSeconds;
    $position = ($diff % $periodInSeconds) / $periodInSeconds;
    if ($position < 0) {
      $position = 1 + $position;
    }

    $this->positionInCycle = $position;
  }

  /**
   * {@inheritdoc}
   */
  public function getPositionInCycle() {
    return $this->positionInCycle;
  }

  /**
   * Sets the number of days until the next full moon.
   */
  private function setDaysUntilNextFullMoon() {
    $position = $this->getPositionInCycle();
    $this->daysUntilNextFullMoon = round((1 - $position) * $this->periodInDays, 2);
  }

  /**
   * {@inheritdoc}
   */
  public function getDaysUntilNextFullMoon() {
    return $this->daysUntilNextFullMoon;
  }

  /**
   * Sets the number of days until the next last quarter moon.
   */
  private function setDaysUntilNextLastQuarterMoon() {
    $days = 0;
    $position = $this->getPositionInCycle();
    if ($position < 0.25) {
      $days = (0.25 - $position) * $this->periodInDays;
    }
    elseif ($position >= 0.25) {
      $days = (1.25 - $position) * $this->periodInDays;
    }

    $this->daysUntilNextLastQuarterMoon = round($days, 1);
  }

  /**
   * {@inheritdoc}
   */
  public function getDaysUntilNextLastQuarterMoon() {
    return $this->daysUntilNextLastQuarterMoon;
  }

  /**
   * Sets the number of days until the next first quarter moon.
   */
  private function setDaysUntilNextFirstQuarterMoon() {
    $days = 0;
    $position = $this->getPositionInCycle();

    if ($position < 0.75) {
      $days = (0.75 - $position) * $this->periodInDays;
    }
    elseif ($position >= 0.75) {
      $days = (1.75 - $position) * $this->periodInDays;
    }

    $this->daysUntilNextFirstQuarterMoon = round($days, 1);
  }

  /**
   * {@inheritdoc}
   */
  public function getDaysUntilNextFirstQuarterMoon() {
    return $this->daysUntilNextFirstQuarterMoon;
  }

  /**
   * Sets the number of days until the next new moon.
   */
  private function setDaysUntilNextNewMoon() {
    $days = 0;
    $position = $this->getPositionInCycle();

    if ($position < 0.5) {
      $days = (0.5 - $position) * $this->periodInDays;
    }
    elseif ($position >= 0.5) {
      $days = (1.5 - $position) * $this->periodInDays;
    }

    $this->daysUntilNextNewMoon = round($days, 1);
  }

  /**
   * {@inheritdoc}
   */
  public function getDaysUntilNextNewMoon() {
    return $this->daysUntilNextNewMoon;
  }

  /**
   * Sets the percentage of illumination for the moon.
   */
  private function setPercentOfIllumination() {
    $percentage = (1.0 + cos(2.0 * M_PI * $this->getPositionInCycle())) / 2.0;
    $percentage *= 100;
    $this->percentOfIllumination = round($percentage, 2);
  }

  /**
   * {@inheritdoc}
   */
  public function getPercentOfIllumination() {
    return $this->percentOfIllumination;
  }

  /**
   * Sets the number of days in a moon phase.
   */
  private function setPeriodInDays() {
    $this->periodInDays = 29.53058867;
  }

  /**
   * Set the number of seconds in a moon phase.
   */
  private function setPeriodInSeconds() {
    $this->periodInSeconds = $this->periodInDays * MP_DAY_IN_SECONDS;
  }

  /**
   * Sets the date for which to calculate the moon phase.
   *
   * @param \DateTime $date
   *   The date to set as a Unix timestamp.
   */
  private function setMoonPhaseDate(\DateTime $date) {
    $this->moonPhaseDate = $date;
    $this->dateAsTimeStamp = strtotime($date->format('Y-m-d H:i:s'));
  }

  /**
   * {@inheritdoc}
   */
  public function getMoonPhaseDate() {
    return $this->moonPhaseDate;
  }

  /**
   * {@inheritdoc}
   */
  public function getDateAsTimestamp() {
    return $this->dateAsTimeStamp;
  }

  /**
   * {@inheritdoc}
   */
  public function getImageUri() {
    $id = $this->getPositionInCycle();
    $phase_id = number_format($id, 2) * 100;
    $phase_id = ($phase_id > 99) ? 00 : $phase_id;
    $path = drupal_get_path('module', 'moon_phases') . '/images/';
    return $path . 'moon.' . str_pad($phase_id, 2, 0, STR_PAD_LEFT) . '.png';
  }

  /**
   * Sets the phase data array.
   */
  private function setAllPhaseData() {
    $this->allPhaseData = [
      MP_NEW_MOON_NAME,
      MP_WAXING_CRESCENT_NAME,
      MP_FIRST_QUARTER_NAME,
      MP_WAXING_GIBBOUS_NAME,
      MP_FULL_MOON_NAME,
      MP_WANING_GIBBOUS_NAME,
      MP_THIRD_QUARTER_MOON_NAME,
      MP_WANING_CRESCENT_NAME,
    ];
  }

  /**
   * Set base date to a known full moon date.
   */
  private function setBaseFullMoonDate() {
    // (http://aa.usno.navy.mil/data/docs/MoonPhase.html)
    $this->baseFullMoonDate = new DateTime('December 12 2008 16:37 UTC');
  }

}
