<?php

namespace Drupal\Tests\search_365\Unit\SearchResults;

use Drupal\search_365\SearchResults\ResultSet;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\search_365\SearchResults\ResultSet
 * @group search_365
 */
class ResultSetTest extends UnitTestCase {

  /**
   * @covers ::getFirstResultIndex
   * @covers ::getLastResultIndex
   * @dataProvider getResultsData
   */
  public function testResultIndex($pageNum, $count, $expectedFirstResult, $expectedLastResult) {
    $resultSet = ResultSet::create()
      ->setPageNum($pageNum)
      ->setPageSize(10)
      ->setResultsCount($count);
    $this->assertEquals($expectedFirstResult, $resultSet->getFirstResultIndex());
    $this->assertEquals($expectedLastResult, $resultSet->getLastResultIndex());
  }

  /**
   * Provides the results data.
   *
   * @return array
   *   The results data.
   */
  public function getResultsData() {
    return [
      [1, 5, 1, 5],
      [1, 25, 1, 10],
      [2, 25, 11, 20],
      [3, 25, 21, 25],
    ];
  }

}
