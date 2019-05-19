<?php

namespace Drupal\Tests\xero\Unit;

/**
 * @group Xero
 */
class XeroQueryOrderTest extends XeroQueryTestBase {

  /**
   * Assert order by method.
   *
   * @dataProvider directionProvider
   */
  public function testOrderBy($direction, $expected) {
    $this->query->orderBy('Name', $direction);
    $options = $this->query->getOptions();
    $this->assertEquals($expected, $options['query']['order']);
  }

  /**
   * Provide options for testing order by directions.
   *
   * @return []
   *   An array of directions and expected values.
   */
  public function directionProvider() {
    return [
      ['ASC', 'Name'],
      ['DESC', 'Name DESC']
    ];
  }

}
