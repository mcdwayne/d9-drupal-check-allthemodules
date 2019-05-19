<?php

namespace Drupal\Tests\views_xml_backend\Unit\Sorter;

use Drupal\views_xml_backend\Sorter\DateSorter;

/**
 * @coversDefaultClass \Drupal\Tests\views_xml_backend\Unit\Sorter\DateSorterTest
 * @group views_xml_backend
 */
class DateSorterTest extends StringSorterTest {

  protected function getSorter($field, $direction) {
    return new DateSorter($field, $direction);
  }

}
