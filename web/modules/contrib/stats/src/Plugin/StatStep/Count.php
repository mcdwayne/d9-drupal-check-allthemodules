<?php

namespace Drupal\stats\Plugin\StatStep;

use Drupal\stats\Annotation\StatStep;
use Drupal\stats\Plugin\StatStepBase;
use Drupal\stats\Row;

/**
 * @StatStep(
 *   id = "count",
 *   label = "Count"
 * )
 */
class Count extends StatStepBase {

  use RowOnlyTrait;
  use SourceArrayToDestinationTrait;

  /**
   * {@inheritdoc}
   */
  protected function transformValue($valArray) {
    return count($valArray);
  }
}
