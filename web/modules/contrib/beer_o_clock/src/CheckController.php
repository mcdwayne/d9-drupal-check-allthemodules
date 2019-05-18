<?php

namespace Drupal\beer_o_clock;

use DateTime;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Utility\Xss;

/**
* Provides route responses for the  module.
*/
class CheckController extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A render array.
   *
   * @throws \Exception
   */
  public function check() {
    $config = \Drupal::config('beer_o_clock.settings');

    $element['beer'] = [
      '#theme' => 'beer_o_clock_block',
      '#message' => Xss::filter($config->get('message')),
      '#not_message' => Xss::filter($config->get('not_message')),
      '#display' => $config->get('display'),
      '#isItBeerOClock' => CheckController::isItBeerOClock(),
      '#attached' => array(
        'library' => ['beer_o_clock/countdown'],
        'drupalSettings' => array(
          'beer_o_clock' => array(
            'timer' => $this->whenIsItNextBeerOClock(),
            'percentage_full' => $this->howMuchBeerWeGot(),
            'day' => $config->get('day'),
            'hour' => $config->get('hour'),
            'duration' => $config->get('duration'),
          )
        )
      ),
    ];

    return $element;
  }

  /**
   * Is it beer o'clock yet.
   *
   * @return bool
   */
  public static function isItBeerOClock() {
    $config = \Drupal::config('beer_o_clock.settings');

    $beer_day = $config->get('day');
    $beer_hour = $config->get('hour');
    $beer_duration = $config->get('duration');
    $today = date('w');
    $hour = date('G');

    return ($beer_day == $today && $hour >= $beer_hour && $hour < $beer_hour + $beer_duration);
  }

  /**
   * Get the number of seconds to beer o'clock.
   *
   * @param DateTime|NULL $boc
   *   You can pass in a random datetime if you want, else it defaults to now.
   * @return int
   *   The number of seconds.
   *
   * @throws \Exception
   */
  public static function whenIsItNextBeerOClock(DateTime $boc = NULL) {
    $config = \Drupal::config('beer_o_clock.settings');

    if (is_null($boc)) {
      $boc = new DateTime();
    }

    $beer_day = $config->get('day');
    $beer_hour = $config->get('hour');
    $today = date('w');
    $hour = date('G');

    if ($beer_day != $today) {
      if ($beer_day < $today) {
        // Makes today negative number.
        $today = $beer_day - $today;
      }

      // If it is more than one it is day day is days other wise.
      (($beer_day - $today) == 1) ? $day = "day" : $day = "days";

      // Finds number of days till beer o'clock (if negative it effectively
      // becomes addition.
      $diff = $beer_day - $today;

      // Increase change beer o'clock to be the next Friday.
      $boc->modify("+" . $diff . " " . $day);
    }
    else {
      if ($beer_hour <= $hour) {
        $boc->modify("+7 days");
      }
    }

    // Change beer o'clock to be correct hour.
    $boc->setTime($beer_hour, 0, 0);
    $boc_timer = $boc->format('U');

    return (int) $boc_timer;
  }

  /**
   * How full is the beer (how close are we to beer o'clock).
   *
   * @return float|int
   * @throws \Exception
   */
  public static function howMuchBeerWeGot() {
    if (CheckController::isItBeerOClock()) {
      return 1;
    }
    $config = \Drupal::config('beer_o_clock.settings');
    $one_week = 604800;
    $boc_duration = $config->get('duration') * 3600;
    $now = \Drupal::time()->getRequestTime();
    $next_boc = CheckController::whenIsItNextBeerOClock();
    $secs_to_next_boc = $next_boc - $now;
    $secs_since_last_boc = $one_week-$secs_to_next_boc - $boc_duration;
    $percentage_full = $secs_since_last_boc / ($one_week - $boc_duration);
    return $percentage_full;
  }

}
