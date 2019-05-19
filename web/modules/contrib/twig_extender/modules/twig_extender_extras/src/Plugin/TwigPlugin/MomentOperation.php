<?php

namespace Drupal\twig_extender_extras\Plugin\TwigPlugin;

/**
 * Provide helper methods for Drupal render elements.
 *
 * @TwigPlugin(
 *   id = "twig_extender_moment_add",
 *   label = @Translation("Identifies the children of an element array, optionally sorted by weight."),
 *   type = "filter",
 *   name = "moment_operation",
 *   function = "moment"
 * )
 */
class MomentOperation extends BaseMoment {

  /**
   * Possibility to add or substract days or weeks ...
   *
   * @param string $date
   *   The element array whose children are to be identified. Passed by
   *   reference.
   * @param bool $operation
   *   Boolean to indicate whether the children should be sorted by weight.
   * @param bool $entry
   *   Boolean to indicate whether the children should be sorted by weight.
   * @param bool $number
   *   Boolean to indicate whether the children should be sorted by weight.
   * @param bool $timezone
   *   Boolean to indicate whether the children should be sorted by weight.
   *
   * @return array
   *   The filtered array to loop over.
   */
  public function moment($date, $operation, $entry, $number, $timezone = NULL) {
    $moment = $this->getMoment($date, $timezone);
    $funcCall = $operation . ucfirst($entry);
    return $moment->$funcCall($number);
  }

}
