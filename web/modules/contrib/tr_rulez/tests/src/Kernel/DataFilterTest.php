<?php

namespace Drupal\Tests\tr_rulez\Kernel;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests using typed data filters.
 *
 * @group tr_rulez
 *
 * @coversDefaultClass \Drupal\typed_data\DataFilterManager
 */
class DataFilterTest extends KernelTestBase {

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected $typedDataManager;

  /**
   * The data filter manager.
   *
   * @var \Drupal\typed_data\DataFilterManagerInterface
   */
  protected $dataFilterManager;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['tr_rulez', 'rules', 'typed_data'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    // @todo this function should be changed to 'protected' as soon as
    // Rules 8.x-3.0-alpha5 is released.
    parent::setUp();
    $this->typedDataManager = $this->container->get('typed_data_manager');
    $this->dataFilterManager = $this->container->get('plugin.manager.typed_data_filter');
  }

  /**
   * Tests the operation of the 'trim' data filter.
   *
   * @covers \Drupal\tr_rulez\Plugin\TypedDataFilter\TrimFilter
   */
  public function testTrimFilter() {
    $filter = $this->dataFilterManager->createInstance('trim');
    $data = $this->typedDataManager->create(DataDefinition::create('string'), ' Text with whitespace ');

    $this->assertTrue($filter->canFilter($data->getDataDefinition()));
    $this->assertFalse($filter->canFilter(DataDefinition::create('any')));

    $this->assertEquals('string', $filter->filtersTo($data->getDataDefinition(), [])->getDataType());

    $this->assertEquals('Text with whitespace', $filter->filter($data->getDataDefinition(), $data->getValue(), []));
  }

  /**
   * Tests the operation of the 'raw' data filter.
   *
   * @covers \Drupal\tr_rulez\Plugin\TypedDataFilter\RawFilter
   */
  public function testRawFilter() {
    $filter = $this->dataFilterManager->createInstance('raw');
    $data = $this->typedDataManager->create(DataDefinition::create('string'), '<b>Test <em>raw</em> filter</b>');

    $this->assertTrue($filter->canFilter($data->getDataDefinition()));
    $this->assertFalse($filter->canFilter(DataDefinition::create('any')));

    $this->assertEquals('string', $filter->filtersTo($data->getDataDefinition(), [])->getDataType());

    $this->assertEquals('<b>Test <em>raw</em> filter</b>', $filter->filter($data->getDataDefinition(), $data->getValue(), []));
  }

  /**
   * Tests the operation of the 'count' data filter.
   *
   * @covers \Drupal\tr_rulez\Plugin\TypedDataFilter\CountFilter
   */
  public function testCountFilter() {
    $filter = $this->dataFilterManager->createInstance('count');
    $data = $this->typedDataManager->create(DataDefinition::create('string'), 'No one shall speak to the Man at the Helm.');

    $this->assertTrue($filter->canFilter($data->getDataDefinition()));
    $this->assertFalse($filter->canFilter(DataDefinition::create('any')));

    $this->assertEquals('integer', $filter->filtersTo($data->getDataDefinition(), [])->getDataType());

    $this->assertEquals(42, $filter->filter($data->getDataDefinition(), $data->getValue(), []));
  }

  /**
   * @covers \Drupal\tr_rulez\Plugin\TypedDataFilter\UpperFilter
   */
  public function testUpperFilter() {
    $filter = $this->dataFilterManager->createInstance('upper');
    $data = $this->typedDataManager->create(DataDefinition::create('string'), 'tEsT');

    $this->assertTrue($filter->canFilter($data->getDataDefinition()));
    $this->assertFalse($filter->canFilter(DataDefinition::create('any')));

    $this->assertEquals('string', $filter->filtersTo($data->getDataDefinition(), [])->getDataType());

    $this->assertEquals('TEST', $filter->filter($data->getDataDefinition(), $data->getValue(), []));
  }

}
