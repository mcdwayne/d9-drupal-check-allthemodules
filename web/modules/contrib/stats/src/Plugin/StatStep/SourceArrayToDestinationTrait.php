<?php
/**
 * @file
 * SimpleSourceToDestinationTrait.php for kartslalom
 */

namespace Drupal\stats\Plugin\StatStep;

use Drupal\stats\Row;

trait SourceArrayToDestinationTrait {

  /**
   * {@inheritdoc}
   */
  protected function processRow(Row $row) {
    $val = $this->getSourceValue($row);
    if (!is_array($val)) {
      throw new \Exception('Source value is not an array.');
    }

    $ret = $this->transformValue($val);
    $this->setDestinationValue($row, $ret);
  }

  /**
   * Transforms the given value.
   *
   * @param array $valArray
   *
   * @return mixed
   */
  abstract protected function transformValue($valArray);

}
