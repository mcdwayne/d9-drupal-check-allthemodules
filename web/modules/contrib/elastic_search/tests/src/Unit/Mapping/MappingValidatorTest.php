<?php

namespace Drupal\Tests\elastic_search\Mapping;

use Drupal\elastic_search\Elastic\ElasticConnectionFactory;
use Drupal\elastic_search\Mapping\MappingValidator;
use Drupal\Tests\UnitTestCase;
use Elasticsearch\Client;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * Class MappingValidatorTest
 *
 * @group elastic_search
 */
class MappingValidatorTest extends UnitTestCase {

  use MockeryPHPUnitIntegration;

  /**
   * Test setting and getting the suffix
   */
  public function testMappingValidatorSuffix() {
    $ecf = \Mockery::mock(ElasticConnectionFactory::class);
    $mv = new MappingValidator($ecf);
    $testSuffix = '_suffix';
    $mv->setSuffix($testSuffix);
    $this->assertEquals($testSuffix, $mv->getSuffix());
  }

  /**
   * @expectedException \Drupal\elastic_search\Exception\MappingValidatorException
   * @expectedExceptionMessage no index key in mapping
   */
  public function testMappingValidatorEmptyMap() {
    $ecf = \Mockery::mock(ElasticConnectionFactory::class);
    $clientMock = \Mockery::mock(Client::class);
    $ecf->shouldReceive('getElasticConnection')->andReturn($clientMock);
    $mv = new MappingValidator($ecf);
    $mv->validate([]);
  }

  /**
   * @expectedException \Drupal\elastic_search\Exception\MappingValidatorException
   * @expectedExceptionMessage no index key in mapping
   */
  public function testMappingValidatorNoIndex() {
    $ecf = \Mockery::mock(ElasticConnectionFactory::class);
    $clientMock = \Mockery::mock(Client::class);
    $ecf->shouldReceive('getElasticConnection')->andReturn($clientMock);
    $mv = new MappingValidator($ecf);
    $mv->validate([
                    'some'    => 'things',
                    'other'   => ['stuff' => 'is', 'here' => 'for'],
                    'testing' => 'purposes',
                    'end'     => new \stdClass(),
                  ]);
  }

  /**
   * Test with a valid map response
   */
  public function testMappingValidatorValidMap() {
    $ecf = \Mockery::mock(ElasticConnectionFactory::class);
    $clientMock = \Mockery::mock(Client::class);
    $clientMock->shouldReceive('indices')->andReturnSelf();
    $clientMock->shouldReceive('create')->andReturn(['acknowledged' => TRUE]);
    $clientMock->shouldReceive('delete')->andReturn(['acknowledged' => TRUE]);
    $ecf->shouldReceive('getElasticConnection')->andReturn($clientMock);
    $mv = new MappingValidator($ecf);
    $this->assertTrue($mv->validate(['index' => 'test_index']));
  }

  /**
   * Test with an invalid map response
   */
  public function testMappingValidatorInvalidMap() {
    $ecf = \Mockery::mock(ElasticConnectionFactory::class);
    $clientMock = \Mockery::mock(Client::class);
    $clientMock->shouldReceive('indices')->andReturnSelf();
    $clientMock->shouldReceive('create')->andReturn(['acknowledged' => FALSE]);
    $clientMock->shouldReceive('delete')->andReturn(['acknowledged' => TRUE]);
    $ecf->shouldReceive('getElasticConnection')->andReturn($clientMock);
    $mv = new MappingValidator($ecf);
    $this->assertFalse($mv->validate(['index' => 'test_index']));
  }

  /**
   * Test with an invalid deletion on the test index
   */
  public function testMappingValidatorInvalidDelete() {
    $ecf = \Mockery::mock(ElasticConnectionFactory::class);
    $clientMock = \Mockery::mock(Client::class);
    $clientMock->shouldReceive('indices')->andReturnSelf();
    $clientMock->shouldReceive('create')->andReturn(['acknowledged' => TRUE]);
    $clientMock->shouldReceive('delete')->andReturn(['acknowledged' => FALSE]);
    $ecf->shouldReceive('getElasticConnection')->andReturn($clientMock);
    $mv = new MappingValidator($ecf);
    $this->assertFalse($mv->validate(['index' => 'test_index']));
  }

  /**
   * Test with the map and the delete invalid
   */
  public function testMappingValidatorInvalidBoth() {
    $ecf = \Mockery::mock(ElasticConnectionFactory::class);
    $clientMock = \Mockery::mock(Client::class);
    $clientMock->shouldReceive('indices')->andReturnSelf();
    $clientMock->shouldReceive('create')->andReturn(['acknowledged' => FALSE]);
    $clientMock->shouldReceive('delete')->andReturn(['acknowledged' => FALSE]);
    $ecf->shouldReceive('getElasticConnection')->andReturn($clientMock);
    $mv = new MappingValidator($ecf);
    $this->assertFalse($mv->validate(['index' => 'test_index']));
  }

  /**
   * Test with the create indices method throwing an exception
   */
  public function testMappingValidatorResponseException() {
    $clientMock = \Mockery::mock(Client::class);
    $clientMock->shouldReceive('indices')->andReturnSelf();
    $clientMock->shouldReceive('create')->andThrow(\Exception::class);
    $clientMock->shouldReceive('delete')->andReturn(['acknowledged' => FALSE]);
    $ecf = \Mockery::mock(ElasticConnectionFactory::class);
    $ecf->shouldReceive('getElasticConnection')->andReturn($clientMock);
    $mv = new MappingValidator($ecf);
    $this->assertFalse($mv->validate(['index' => 'test_index']));
  }

}
