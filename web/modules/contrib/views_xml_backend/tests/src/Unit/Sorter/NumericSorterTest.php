<?php

namespace Drupal\Tests\views_xml_backend\Unit\Sorter;

use Drupal\views_xml_backend\Sorter\NumericSorter;

/**
 * @coversDefaultClass \Drupal\Tests\views_xml_backend\Unit\Sorter\NumericSorterTest
 * @group views_xml_backend
 */
class NumericSorterTest extends StringSorterTest {

  protected function getSorter($field, $direction) {
    return new NumericSorter($field, $direction);
  }

}
