<?php

namespace Drupal\twig_extender_extras\Plugin\TwigPlugin;

use Moment\CustomFormats\MomentJs;

/**
 * Provide helper methods for Drupal render elements.
 *
 * @TwigPlugin(
 *   id = "twig_extender_moment_format",
 *   label = @Translation("Identifies the children of an element array, optionally sorted by weight."),
 *   type = "filter",
 *   name = "moment_format",
 *   function = "moment"
 * )
 */
class MomentFormat extends BaseMoment {

  /**
   * Format a date with moment php.
   *
   * @param mixed $date
   *   The element array whose children are to be identified. Passed by
   *   reference.
   * @param string $format
   *   Boolean to indicate whether the children should be sorted by weight.
   * @param mixed $timezone
   *   Boolean to indicate whether the children should be sorted by weight.
   * @param mixed $js
   *   Boolean to indicate whether the children should be sorted by weight.
   *
   * @return array
   *   Array for render.
   *
   * @throws \Exception
   */
  public function moment($date, $format, $timezone = NULL, $js = FALSE) {

    $moment = $this->getMoment($date, $timezone);

    $value = $moment->format($format);
    if ($js === TRUE) {
      $value = $moment->format($format, new MomentJs());
    }

    $build = [
      '#cache' => [
        'contexts' => ['languages', 'timezone'],
      ],
      '#markup' => $value,
    ];

    return $build;
  }

}
