<?php

namespace Drupal\stats\Plugin\StatStep;

use Drupal\stats\Annotation\StatStep;
use Drupal\stats\Plugin\StatStepBase;
use Drupal\stats\Row;

/**
 * @StatStep(
 *   id = "get",
 *   label = "Get"
 * )
 */
class Get extends StatStepBase {

  use RowOnlyTrait;
  use SourceToDestinationTrait;

  /**
   * {@inheritdoc}
   */
  protected function transformValue($val) {
    // Do nothing.
    return $val;
  }
}
