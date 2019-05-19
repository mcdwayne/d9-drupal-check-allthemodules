<?php

namespace Drupal\stats\Plugin\StatStep;

use Drupal\stats\Annotation\StatStep;
use Drupal\stats\Plugin\StatStepBase;
use Drupal\stats\Row;

/**
 * @StatStep(
 *   id = "sum",
 *   label = "Sum"
 * )
 */
class Sum extends StatStepBase {

  use RowOnlyTrait;
  use SourceArrayToDestinationTrait;

  /**
   * {@inheritdoc}
   */
  protected function transformValue($valArray) {
    $sum = 0;
    if (!empty($this->configuration['property'])) {
      foreach ($valArray as $val) {
        $sum += Row::getNestedValue($val, $this->configuration['property']);
      }
    }
    else {
      $sum = array_sum($valArray);
    }
    return $sum;
  }
}
