<?php

namespace Drupal\moon_phases;

/**
 * Interface MoonCalcInterface.
 */
interface MoonCalcInterface {

  /**
   * Returns the moon phase ID.
   *
   * @return string
   *   Returns the ID for a given moon phase.
   */
  public function getMoonPhaseId();

  /**
   * Returns the moon phase name.
   *
   * @return string
   *   Returns the ID for a given moon name.
   */
  public function getMoonPhaseName();

  /**
   * Returns the position in the moon phase cycle.
   *
   * @return float
   *   Return the position in the phase cycle.
   */
  public function getPositionInCycle();

  /**
   * Get the number of days until the next full moon.
   *
   * @return int
   *   Returns the number of days until the next full moon.
   */
  public function getDaysUntilNextFullMoon();

  /**
   * Get the number of days until the next last quarter moon.
   *
   * @return int
   *   Returns the number of days until the next last quarter moon.
   */
  public function getDaysUntilNextLastQuarterMoon();

  /**
   * Get the number of days until the next first quarter moon.
   *
   * @return int
   *   Returns the number of days until the next first quarter moon.
   */
  public function getDaysUntilNextFirstQuarterMoon();

  /**
   * Get the number of days until the next new moon.
   *
   * @return int
   *   Returns the number of days until the next new moon.
   */
  public function getDaysUntilNextNewMoon();

  /**
   * Get the percentage of illumination for the moon phase.
   *
   * @return float
   *   Returns the percentage of illumination.
   */
  public function getPercentOfIllumination();

  /**
   * Returns the moon phase date as a DateTime object.
   *
   * @return \DateTime
   *   Returns the date as a DateTime object.
   */
  public function getMoonPhaseDate();

  /**
   * Returns the moon phase date as a Unix timestamp.
   *
   * @return int
   *   Returns a timestamp of the date being calculated.
   */
  public function getDateAsTimestamp();

  /**
   * Creates a path to the moon phase image.
   *
   * @return string
   *   Returns the URI for moon phase image.
   */
  public function getImageUri();

}
