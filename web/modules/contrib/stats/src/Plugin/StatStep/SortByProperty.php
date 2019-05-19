<?php

namespace Drupal\stats\Plugin\StatStep;

use Drupal\stats\Annotation\StatStep;
use Drupal\stats\Plugin\StatStepBase;
use Drupal\stats\Row;

/**
 * @StatStep(
 *   id = "sort_by_property",
 *   label = "Sort by property"
 * )
 */
class SortByProperty extends StatStepBase {

  use RowOnlyTrait;
  use SourceArrayToDestinationTrait;

  /**
   * {@inheritdoc}
   */
  protected function transformValue($valArray) {
    usort($valArray, $this->sortCallback($this->configuration['property']));
    return $valArray;
  }

  /**
   * Generates sorting callback for given configuration.
   *
   * @param array $properties
   *
   * @return \Closure
   */
  protected function sortCallback($properties) {
    return function ($a, $b) use ($properties) {
      foreach ($properties as $property => $direction) {
        $a_p = $this->getProperty($a, $property);
        $b_p = $this->getProperty($b, $property);
        if ($a_p != $b_p) {
          $factor = (strtolower($direction) == 'desc') ? -1 : 1;
          return (($a_p > $b_p) * 2 - 1) * $factor;
        }
      }
      return 0;
    };
  }
}
