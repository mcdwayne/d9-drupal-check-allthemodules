<?php
/**
 * @file
 * SimpleSourceToDestinationTrait.php for kartslalom
 */

namespace Drupal\stats\Plugin\StatStep;

use Drupal\stats\Row;

trait SourceToDestinationTrait {

  /**
   * {@inheritdoc}
   */
  protected function processRow(Row $row) {
    $val = $this->getSourceValue($row);
    $ret = $this->transformValue($val);
    $this->setDestinationValue($row, $ret);
  }

  /**
   * Transforms the given value.
   *
   * @param mixed $val
   *
   * @return mixed
   */
  abstract protected function transformValue($val);

}
