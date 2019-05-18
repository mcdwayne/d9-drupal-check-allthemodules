<?php

namespace Drupal\datetime_testing;

use Drupal\Component\Datetime\TimeInterface;

/**
 * Defines an interface for manipulating the apparent Drupal time.
 *
 * This is particularly intended to be helpful when writing functional tests,
 * because Drupal's time service can be decorated with an implementation of this
 * class that uses Drupal's state API to persist these manipuated times across
 * requests. Merely mocking the time service is sufficient for unit tests but
 * not for functional tests.
 */
interface TestTimeInterface extends TimeInterface {

  /**
   * Sets the time as understood by \Drupal::time().
   *
   * This method causes Drupal' Time service to change its understanding of what
   * the current moment of date/time is. It does not pin the time to that
   * moment. For example, if this method is used to set the time as 23:00:00 on
   * some day, then 10 seconds later if the time service's ::getCurrentTime was
   * called it would return a timestamp equivalent to 23:00:10 on that day.
   *
   * Call ::freezeTime before calling this method if you want to fix the time to
   * an unchanging point.
   *
   * Integer or float values are interpreted as Unix timestamps. String values
   * are interpreted using a version of PHP's strtotime() logic, but taking into
   * account the current time as this class understands it, and the default
   * Drupal timezone.
   *
   * @param int|float|string $time
   *   A Unix timestamp (can be fractional), or a string to interpret as such.
   *
   * @see \Drupal\datetime_testing\TestTimeInterface::resetTime()
   * @see \Drupal\datetime_testing\TestTimeInterface::freezeTime()
   * @see \Drupal\datetime_testing\TestTimeInterface::unfreezeTime()
   * @see \Drupal\datetime_testing\TestTimeInterface::getCurrentTime()
   * @see \Drupal\datetime_testing\TestDateTime::construct()
   */
  public function setTime($time);

  /**
   * Removes all ongoing manipulations of the time.
   *
   * The true current time is restored, and time is unfrozen (if frozen).
   *
   * @see \Drupal\datetime_testing\TestTimeInterface::setTime()
   * @see \Drupal\datetime_testing\TestTimeInterface::shiftTime()
   * @see \Drupal\datetime_testing\TestTimeInterface::freezeTime()
   * @see \Drupal\datetime_testing\TestTimeInterface::unfreezeTime()
   * @see \Drupal\datetime_testing\TestTimeInterface::getCurrentTime()
   */
  public function resetTime();

  /**
   * Freezes the time.
   *
   * This method causes Drupal's Time service to fix its understanding of what
   * the current moment of date/time is. For example, if the time is currently
   * understood to be 23:00:00 on some day, then 10 seconds later if the Time
   * service's ::getCurrentTime were called it would still return a timestamp
   * equivalent to 23:00:00 on that day.
   *
   * @see \Drupal\datetime_testing\TestTimeInterface::setTime()
   * @see \Drupal\datetime_testing\TestTimeInterface::shiftTime()
   * @see \Drupal\datetime_testing\TestTimeInterface::resetTime()
   * @see \Drupal\datetime_testing\TestTimeInterface::unfreezeTime()
   * @see \Drupal\datetime_testing\TestTimeInterface::getCurrentTime()
   */
  public function freezeTime();

  /**
   * Unfreezes the time.
   *
   * This method stops the freezing of time induced by ::freezeTime() and allows
   * time to flow freely again, but it does not restore the lost time. For
   * example, if the time were understood to be 23:00:00 on some day, and
   * ::freezeTime() were called, and then 10 seconds later ::()unfreeze was
   * called, when a further 10 seconds elapsed the time would be understood to
   * be 23:00:10 on that day despite twenty seconds having passed in reality.
   *
   * @see \Drupal\datetime_testing\TestTimeInterface::freezeTime()
   * @see \Drupal\datetime_testing\TestTimeInterface::getCurrentTime()
   */
  public function unfreezeTime();

}
