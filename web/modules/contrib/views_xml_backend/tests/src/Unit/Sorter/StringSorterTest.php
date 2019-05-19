<?php

namespace Drupal\Tests\views_xml_backend\Unit\Sorter;

use Drupal\Tests\views_xml_backend\Unit\ViewsXmlBackendTestBase;
use Drupal\views\ResultRow;
use Drupal\views_xml_backend\Sorter\StringSorter;

/**
 * @coversDefaultClass \Drupal\Tests\views_xml_backend\Unit\Sorter\StringSorterTest
 * @group views_xml_backend
 */
class StringSorterTest extends ViewsXmlBackendTestBase {

  public function testAsc() {
    $sorter = $this->getSorter('field', 'ASC');

    $result = [
      new ResultRow(['field' => ['5'], 'index' => 0]),
      new ResultRow(['field' => ['4'], 'index' => 1]),
      new ResultRow(['field' => ['1'], 'index' => 2]),
      new ResultRow(['field' => ['1'], 'index' => 3]),
      new ResultRow(['field' => ['3'], 'index' => 4]),
      new ResultRow(['field' => ['2'], 'index' => 5]),
    ];

    $sorter($result);

    $values = [2 => '1', 3 => '1', 5 => '2', 4 => '3', 1 => '4', 0 => '5'];

    foreach ($values as $index => $value) {
      $row = array_shift($result);
      $this->assertSame([$value], $row->field);
      $this->assertSame($index, $row->index);
    }
  }

  public function testDesc() {
    $sorter = $this->getSorter('field', 'DESC');

    $result = [
      new ResultRow(['field' => ['5'], 'index' => 0]),
      new ResultRow(['field' => ['4'], 'index' => 1]),
      new ResultRow(['field' => ['1'], 'index' => 2]),
      new ResultRow(['field' => ['1'], 'index' => 3]),
      new ResultRow(['field' => ['3'], 'index' => 4]),
      new ResultRow(['field' => ['2'], 'index' => 5]),
    ];

    $sorter($result);

    $values = [0 => '5', 1 => '4', 4 => '3', 5 => '2', 2 => '1', 3 => '1'];

    foreach ($values as $index => $value) {
      $row = array_shift($result);
      $this->assertSame([$value], $row->field);
      $this->assertSame($index, $row->index);
    }
  }

  protected function getSorter($field, $direction) {
    return new StringSorter($field, $direction);
  }

}
