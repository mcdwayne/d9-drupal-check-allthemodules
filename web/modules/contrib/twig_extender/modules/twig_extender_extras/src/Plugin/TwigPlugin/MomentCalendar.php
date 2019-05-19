<?php

namespace Drupal\twig_extender_extras\Plugin\TwigPlugin;

/**
 * Provide helper methods for Drupal render elements.
 *
 * @TwigPlugin(
 *   id = "twig_extender_moment_calendar",
 *   label = @Translation("Identifies the children of an element array, optionally sorted by weight."),
 *   type = "filter",
 *   name = "moment_calendar",
 *   function = "moment"
 * )
 */
class MomentCalendar extends BaseMoment {

  /**
   * Return date formatted in a relative date.
   *
   * @param string $date
   *   The element array whose children are to be identified. Passed by
   *   reference.
   * @param string $timezone
   *   Boolean to indicate whether the children should be sorted by weight.
   *
   * @return array
   *   Array for render.
   *
   * @throws \Exception
   *
   * @see https://github.com/fightbulc/moment.php
   */
  public function moment($date, $timezone = NULL) {
    $moment = $this->getMoment($date, $timezone);

    $build = [
      '#cache' => [
        'contexts' => ['languages', 'timezone'],
      ],
      '#markup' => $moment->calendar(),
    ];

    return $build;
  }

}
