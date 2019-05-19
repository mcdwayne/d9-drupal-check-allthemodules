<?php

namespace Drupal\stats\Plugin\StatStep;

use Drupal\stats\Annotation\StatStep;
use Drupal\stats\Plugin\StatStepBase;
use Drupal\stats\Row;

/**
 * @StatStep(
 *   id = "array_column",
 *   label = "Array Column"
 * )
 */
class ArrayColumn extends StatStepBase {

  use RowOnlyTrait;
  use SourceArrayToDestinationTrait;

  /**
   * {@inheritdoc}
   */
  protected function transformValue($valArray) {
    $column_key = $this->configuration['column_key'];
    $index_key = $this->configuration['index_key'] ?: NULL;
    return array_column($valArray, $column_key, $index_key);
  }
}
