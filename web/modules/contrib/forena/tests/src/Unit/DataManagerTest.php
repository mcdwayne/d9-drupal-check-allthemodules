<?php
/**
 * @file
 * Implements DataManagerTest
 */

namespace Drupal\Tests\forena\Unit;

use Drupal\Tests\forena\Unit\Mock\TestingDataManager;

/**
 * @group Forena
 * @require module forena
 * @coversDefaultClass \Drupal\forena\DataManager
 */
class DataManagerTest extends FrxTestCase {

  public $dmSvc;

  public function setUp() {
    $this->dmSvc = new TestingDataManager();
  }

  /**
   * Test XML File Data Source
   */
  public function testFrxFileDriver() {
    // Check the data source.
    $dataSource = $this->dmSvc->repository('test');
    $this->assertInstanceOf('\Drupal\forena\FrxPlugin\Driver\FrxFiles', $dataSource);
    // Validate a functional XML pull from driver
    $xml = $this->dmSvc->data('test/simple_data');
    $this->assertInstanceOf('\SimpleXMLElement', $xml);
    // Verify that there is a row
    $row = $xml->row;
    $this->assertInstanceOf('\SimpleXMLElement', $row);
  }
}