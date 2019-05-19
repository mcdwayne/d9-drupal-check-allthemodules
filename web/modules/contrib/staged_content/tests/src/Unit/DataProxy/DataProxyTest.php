<?php

namespace Drupal\Tests\staged_content\Unit\DataProxy;

use Drupal\staged_content\DataProxy\JsonDataProxy;
use Drupal\Tests\UnitTestCase;

/**
 * Class DataProxyTest.
 */
class JsonDataProxyTest extends UnitTestCase {

  /**
   * @covers Drupal\staged_content\DataProxy\JsonDataProxy::__construct
   */
  public function testConstruct() {
    $dataProxy = new JsonDataProxy('fileName', 'uuid-for-this-item', 'entity_type', 'marker');
    $this->assertInstanceOf('Drupal\staged_content\DataProxy\DataProxyInterface', $dataProxy);
  }

  /**
   * @covers Drupal\staged_content\DataProxy\JsonDataProxy::getData
   * @covers Drupal\staged_content\DataProxy\JsonDataProxy::getRawData
   */
  public function testGetData() {
    $dataProxy = new JsonDataProxy($this->provideFixtureDir() . 'json_data_files/dummy-file.json', 'uuid-for-this-item', 'entity_type', 'marker');
    $this->assertEquals(['meta' => 'hahah'], $dataProxy->getData());
  }

  /**
   * @covers Drupal\staged_content\DataProxy\JsonDataProxy::getUuid
   */
  public function testGetUuid() {
    $dataProxy = new JsonDataProxy('fileName', 'uuid-for-this-item', 'entity_type', 'marker');
    $this->assertEquals('uuid-for-this-item', $dataProxy->getUuid());
  }

  /**
   * @covers Drupal\staged_content\DataProxy\JsonDataProxy::getEntityType
   */
  public function testGetEntityType() {
    $dataProxy = new JsonDataProxy('fileName', 'uuid-for-this-item', 'entity_type', 'marker');
    $this->assertEquals('entity_type', $dataProxy->getEntityType());
  }

  /**
   * Get the location for the fixture files.
   *
   * @TODO Move this to a trait.
   *
   * @return string
   *   Location for the fixture files root.
   */
  protected function provideFixtureDir() {
    return dirname(dirname(__DIR__)) . '/fixtures/';
  }

}
