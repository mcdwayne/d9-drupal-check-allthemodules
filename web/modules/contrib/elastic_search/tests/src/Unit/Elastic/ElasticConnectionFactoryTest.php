<?php

namespace Drupal\Tests\elastic_search\Unit\Elastic;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\elastic_search\Elastic\ElasticConnectionFactory;
use Drupal\Tests\UnitTestCase;
use Elasticsearch\Client;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

/**
 * ElasticConnectionFactoryTest
 *
 * @group elastic_search
 */
class ElasticConnectionFactoryTest extends UnitTestCase {

  use MockeryPHPUnitIntegration;

  /**
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage scheme key not found in configuration
   */
  public function testConnectionFactoryNoConfig() {
    $immutableConfig = \Mockery::mock(ImmutableConfig::class);
    $logger = \Mockery::mock(LoggerChannelFactoryInterface::class);
    $translation = \Mockery::mock(TranslationInterface::class);
    $immutableConfig->shouldReceive('getRawData')->andReturn([
                                                               'advanced' => [
                                                                 'validate' => [
                                                                   'active' => 1,
                                                                 ],
                                                               ],
                                                             ]); //getRawData
    $connectionFactory = new ElasticConnectionFactory($immutableConfig,
                                                      $logger,
                                                      $translation);
    $connectionFactory->getElasticConnection();
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testConnectionFactoryOnlyScheme() {
    $immutableConfig = \Mockery::mock(ImmutableConfig::class);
    $logger = \Mockery::mock(LoggerChannelFactoryInterface::class);
    $translation = \Mockery::mock(TranslationInterface::class);
    $immutableConfig->shouldReceive('getRawData')
                    ->andReturn([
                                  'scheme'   => 'http',
                                  'advanced' => [
                                    'validate' => [
                                      'active' => 1,
                                    ],
                                  ],
                                ]); //getRawData
    $connectionFactory = new ElasticConnectionFactory($immutableConfig,
                                                      $logger,
                                                      $translation);
    $connectionFactory->getElasticConnection();
  }

  /**
   * @expectedException \InvalidArgumentException
   */
  public function testConnectionFactoryWithPort() {
    $immutableConfig = \Mockery::mock(ImmutableConfig::class);
    $logger = \Mockery::mock(LoggerChannelFactoryInterface::class);
    $translation = \Mockery::mock(TranslationInterface::class);
    $immutableConfig->shouldReceive('getRawData')
                    ->andReturn([
                                  'scheme'   => 'http',
                                  'port'     => 1234,
                                  'advanced' => [
                                    'validate' => [
                                      'active' => 1,
                                    ],
                                  ],
                                ]); //getRawData
    $connectionFactory = new ElasticConnectionFactory($immutableConfig,
                                                      $logger,
                                                      $translation);
    $connectionFactory->getElasticConnection();
  }

  /**
   * Test with the host value set
   */
  public function testConnectionFactoryWithHost() {
    $immutableConfig = \Mockery::mock(ImmutableConfig::class);
    $logger = \Mockery::mock(LoggerChannelFactoryInterface::class);
    $translation = \Mockery::mock(TranslationInterface::class);
    $immutableConfig->shouldReceive('getRawData')
                    ->andReturn([
                                  'scheme' => 'http',
                                  'port'   => 1234,
                                  'host'   => 'www.url.com',
                                ]); //getRawData
    $connectionFactory = new ElasticConnectionFactory($immutableConfig,
                                                      $logger,
                                                      $translation);
    $this->assertInstanceOf('\Elasticsearch\Client',
                            $connectionFactory->getElasticConnection());
  }

  /**
   * Test with validate on
   */
  public function testConnectionFactoryTestValidateActive() {
    $immutableConfig = \Mockery::mock(ImmutableConfig::class);
    $logger = \Mockery::mock(LoggerChannelFactoryInterface::class);
    $logger->shouldReceive('get')->andReturnSelf();
    $logger->shouldReceive('critical')->andReturn();
    $translation = \Mockery::mock(TranslationInterface::class);
    $translation->shouldReceive('t')->andReturn('i am a string');
    $advanced = [];
    $advanced['validate']['active'] = TRUE;
    $immutableConfig->shouldReceive('getRawData')
                    ->andReturn([
                                  'scheme'   => 'http',
                                  'port'     => 1234,
                                  'host'     => 'www.url.com',
                                  'advanced' => $advanced,
                                ]); //getRawData
    $connectionFactory = new ElasticConnectionFactory($immutableConfig,
                                                      $logger,
                                                      $translation);
    $connection = $connectionFactory->getElasticConnection();
    $this->assertNull($connection);
  }

  /**
   * @expectedException \Exception
   */
  public function testConnectionFactoryValidateActiveDieHard() {

    $immutableConfig = \Mockery::mock(ImmutableConfig::class);
    $logger = \Mockery::mock(LoggerChannelFactoryInterface::class);
    $logger->shouldReceive('get')->andReturnSelf();
    $logger->shouldReceive('critical')->andReturn();
    $translation = \Mockery::mock(TranslationInterface::class);
    $advanced = [];
    $advanced['validate']['active'] = TRUE;
    $advanced['validate']['die_hard'] = TRUE;
    $immutableConfig->shouldReceive('getRawData')
                    ->andReturn([
                                  'scheme'   => 'http',
                                  'port'     => 1234,
                                  'host'     => 'www.url.com',
                                  'advanced' => $advanced,
                                ]); //getRawData
    $connectionFactory = new ElasticConnectionFactory($immutableConfig,
                                                      $logger,
                                                      $translation);
    $connection = $connectionFactory->getElasticConnection();
  }

  /**
   * Test setting a logger
   */
  public function testLogging() {
    $immutableConfig = \Mockery::mock(ImmutableConfig::class);
    $logger = \Mockery::mock(LoggerChannelFactoryInterface::class);
    $logger->shouldReceive('get')
           ->times()
           ->andReturn(\Mockery::mock(LoggerChannelInterface::class));
    $translation = \Mockery::mock(TranslationInterface::class);
    $advanced = [];
    $advanced['developer']['active'] = TRUE;//turn on logging
    $immutableConfig->shouldReceive('getRawData')
                    ->andReturn([
                                  'scheme'   => 'http',
                                  'port'     => 1234,
                                  'host'     => 'www.url.com',
                                  'advanced' => $advanced,
                                ]); //getRawData
    $connectionFactory = new ElasticConnectionFactory($immutableConfig,
                                                      $logger,
                                                      $translation);
    $connection = $connectionFactory->getElasticConnection();
    $this->assertInstanceOf(Client::class, $connection);
  }

  /**
   * Test that we can get something expected from the getConnection method if
   * 'connection' is set
   */
  public function testConnectionFactoryHasConnection() {
    $immutableConfig = \Mockery::mock(ImmutableConfig::class);
    $logger = \Mockery::mock(LoggerChannelFactoryInterface::class);
    $translation = \Mockery::mock(TranslationInterface::class);
    $connectionFactory = new ElasticConnectionFactory($immutableConfig,
                                                      $logger,
                                                      $translation);
    $connection = $this->makePublic($connectionFactory, 'connection');
    $fakeData = ['falsify' => 'everything'];
    $connection->setValue($connectionFactory, $fakeData);
    $this->assertEquals($fakeData, $connectionFactory->getElasticConnection());
  }

  /**
   * Test with authentication values.
   */
  public function testWithAuth() {
    $immutableConfig = \Mockery::mock(ImmutableConfig::class);
    $auth['username'] = 'user';
    $auth['password'] = 'pass';
    $immutableConfig->shouldReceive('getRawData')
                    ->andReturn([
                                  'scheme' => 'http',
                                  'port'   => 1234,
                                  'host'   => 'www.url.com',
                                  'auth'   => $auth,
                                ]); //getRawData
    $logger = \Mockery::mock(LoggerChannelFactoryInterface::class);
    $translation = \Mockery::mock(TranslationInterface::class);
    $connectionFactory = new ElasticConnectionFactory($immutableConfig,
                                                      $logger,
                                                      $translation);
    $this->assertInstanceOf(Client::class,
                            $connectionFactory->getElasticConnection());
  }

  /**
   * @param mixed  $obj
   * @param string $property
   *
   * @return \ReflectionProperty
   */
  protected function makePublic($obj, string $property) {
    $reflect = new \ReflectionObject($obj);
    $property = $reflect->getProperty($property);
    $property->setAccessible(TRUE);
    return $property;
  }

}
