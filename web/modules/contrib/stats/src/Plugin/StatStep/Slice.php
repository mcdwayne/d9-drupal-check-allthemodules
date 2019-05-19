<?php

namespace Drupal\stats\Plugin\StatStep;

use Drupal\stats\Annotation\StatStep;
use Drupal\stats\Plugin\StatStepBase;
use Drupal\stats\Row;

/**
 * @StatStep(
 *   id = "slice",
 *   label = "Slice"
 * )
 */
class Slice extends StatStepBase {

  use RowOnlyTrait;
  use SourceArrayToDestinationTrait;

  /**
   * {@inheritdoc}
   */
  protected function transformValue($valArray) {
    $offset = $this->configuration['offset'] ?: 0;
    $length = $this->configuration['length'] ?: NULL;
    return array_slice($valArray, $offset, $length);
  }
}
