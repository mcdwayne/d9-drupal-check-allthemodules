<?php

namespace Drupal\twig_extender_extras\Plugin\TwigPlugin;

/**
 * Provide helper methods for Drupal render elements.
 *
 * @TwigPlugin(
 *   id = "twig_extender_moment_difference",
 *   label = @Translation("Identifies the children of an element array, optionally sorted by weight."),
 *   type = "filter",
 *   name = "moment_difference",
 *   function = "moment"
 * )
 */
class MomentDifference extends BaseMoment {

  /**
   * Get a difference between two dates.
   *
   * @param string $from
   *   Start date.
   * @param mixed $to
   *   Date is relative to.
   * @param mixed $operation
   *   Operation. Could be:
   *   relative|direction|seconds|minutes|hours|days|weeks|months|years.
   * @param mixed $timezone
   *   Boolean to indicate whether the children should be sorted by weight.
   *
   * @return array
   *   String for render.
   *
   * @see https://github.com/fightbulc/moment.php
   */
  public function moment($from, $to = NULL, $operation = 'direction', $timezone = NULL) {
    $moment = $this->getMoment($to, $timezone);
    $from = $this->getMoment($from, $timezone);
    $funcCall = 'get' . ucfirst($operation);
    return $moment->from($from->format())->$funcCall();
  }

}
