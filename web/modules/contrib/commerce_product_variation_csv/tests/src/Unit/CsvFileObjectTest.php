<?php

namespace Drupal\Tests\commerce_product_variation_csv\Unit;

use Drupal\commerce_product_variation_csv\CsvFileObject;
use Drupal\Tests\UnitTestCase;

/**
 * @group commerce_product_variation_csv
 */
class CsvFileObjectTest extends UnitTestCase {

  public function testCsvFile() {
    $csv = new CsvFileObject(__DIR__ . '/../../fixtures/variations_generated_titles.csv', TRUE);
    self::assertEquals(2, $csv->count());

    $current = $csv->current();
    self::assertEquals([
      'sku' => 'SKU1234',
      'status' => '1',
      'list_price__number' => '',
      'list_price__currency_code' => '',
      'price__number' => '12.00',
      'price__currency_code' => 'USD',
    ], $current);

    $csv = new CsvFileObject(__DIR__ . '/../../fixtures/variations_with_titles.csv', TRUE);
    self::assertEquals(2, $csv->count());

    $current = $csv->current();
    self::assertEquals([
      'sku' => 'SKU1234',
      'status' => '1',
      'title' => 'My Product 1234',
      'list_price__number' => '',
      'list_price__currency_code' => '',
      'price__number' => '12.00',
      'price__currency_code' => 'USD',
    ], $current);
  }

}
