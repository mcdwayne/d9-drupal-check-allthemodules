<?php

namespace Drupal\stats\Plugin\StatStep;

use Drupal\stats\Annotation\StatStep;
use Drupal\stats\Plugin\StatStepBase;
use Drupal\stats\Row;

/**
 * @StatStep(
 *   id = "default_value",
 *   label = "Default value"
 * )
 */
class DefaultValue extends StatStepBase {

  use RowOnlyTrait;
  use SourceToDestinationTrait;

  /**
   * {@inheritdoc}
   */
  protected function transformValue($val) {
    if (!isset($val)) {
      return $this->configuration['default_value'];
    }
    return $val;
  }
}
