<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 10.02.17
 * Time: 00:37
 */

namespace Drupal\Tests\elastic_search\Unit\Utility;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\elastic_search\Plugin\FieldMapperInterface;
use Drupal\elastic_search\Plugin\FieldMapperManager;
use Drupal\elastic_search\Utility\TypeMapper;
use Drupal\Tests\UnitTestCase;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class TypeMapperTest
 *
 * @group elastic_search
 */
class TypeMapperTest extends UnitTestCase {

  use MockeryPHPUnitIntegration;
  /**
   * @var array
   */
  protected static $cacheData = [
    'supports' => [
      'text' => [
        'keyword' => 'keyword',
        'text'    => 'text',
      ],
    ],
  ];

  /**
   * @var array
   */
  protected static $cacheDataAll = [
    'supports' => [
      'text' => [
        'keyword' => 'keyword',
        'text'    => 'text',
        'none'    => 'none',
      ],
      'all'  => [
        'none' => 'none',
      ],
    ],
  ];

  /**
   * @var array
   */
  protected static $formFieldArray = ['test' => 'success'];

  /**
   * @param mixed $cacheBackendInterface
   *
   * @return \Drupal\elastic_search\Utility\TypeMapper
   */
  private function getTypeMapper($cacheBackendInterface) {

    $fieldMapperManager = \Mockery::mock(FieldMapperManager::class);
    $fieldMapperManager->shouldReceive('createInstance')
                       ->times()
                       ->andReturnUsing([
                                          $this,
                                          'fieldMapperCreateInstanceCallback',
                                        ]);

    $definitions = [
      'text' => [
        'id'       => 'text',
        'label'    => \Mockery::mock(TranslatableMarkup::class),
        'class'    => 'Drupal\\elastic_search\\Plugin\\FieldMapper\\Text',
        'provider' => 'elastic_search',
      ],
    ];
    $fieldMapperManager->shouldReceive('getDefinitions')
                       ->times()
                       ->andReturn($definitions);

    $logger = \Mockery::mock(LoggerInterface::class);
    $eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);
    $eventDispatcher->shouldReceive('dispatch')->times()->andReturnUsing([
                                                                           $this,
                                                                           'eventDispatchDispatchCallback',
                                                                         ]);

    return new TypeMapper($fieldMapperManager,
                          $cacheBackendInterface,
                          $logger,
                          $eventDispatcher);
  }

  /**
   * @param string $id
   * @param mixed  $event
   *
   * @return mixed
   */
  public function eventDispatchDispatchCallback($id, $event) {
    return $event;
  }

  /**
   * @param string $type
   *
   * @return \Mockery\MockInterface
   */
  public function fieldMapperCreateInstanceCallback($type) {

    $mock = \Mockery::mock(FieldMapperInterface::class);
    $mock->shouldReceive('getFormFields')
         ->times()
         ->andReturn(self::$formFieldArray);
    $mock->shouldReceive('supportsFields')->times()->andReturn(TRUE);
    if ($type === 'text') {
      $mock->shouldReceive('getSupportedTypes')->times()->andReturn([
                                                                      'text',
                                                                      'text_long',
                                                                      'uri',
                                                                      'link',
                                                                      'string',
                                                                      'string_long',
                                                                      'token',
                                                                      'uuid',
                                                                      'language',
                                                                      'path',
                                                                      'email',
                                                                    ]);
    } elseif ($type === 'none') {
      $mock->shouldReceive('getSupportedTypes')->times()->andReturn([
                                                                      'all',
                                                                    ]);
    }

    return $mock;

  }

  /**
   * @param bool $all
   * @param bool $noCache
   *
   * @return \Mockery\MockInterface
   */
  private function getCacheBackendInterface(bool $all = FALSE,
                                            $noCache = FALSE) {

    $cacheBackendInterface = \Mockery::mock(CacheBackendInterface::class);

    if ($noCache) {
      $firstCache = [$this, 'cacheBackendReturnNull'];
      $otherCache = [$this, 'cacheBackendReturnCacheObject'];
      $cacheBackendInterface->shouldReceive('get')
                            ->times()
                            ->andReturnUsing($firstCache,
                                             $otherCache,
                                             $otherCache,
                                             $otherCache,
                                             $otherCache,
                                             $otherCache);
    } else if ($all) {
      $firstCache = [$this, 'cacheBackendReturnCacheObjectAll'];
      $cacheBackendInterface->shouldReceive('get')
                            ->times()
                            ->andReturnUsing($firstCache,
                                             $firstCache,
                                             $firstCache,
                                             $firstCache,
                                             $firstCache,
                                             $firstCache);

    } else {
      $firstCache = [$this, 'cacheBackendReturnCacheObject'];
      $cacheBackendInterface->shouldReceive('get')
                            ->times()
                            ->andReturnUsing($firstCache,
                                             $firstCache,
                                             $firstCache,
                                             $firstCache,
                                             $firstCache,
                                             $firstCache);
    }

    $cacheBackendInterface->shouldReceive('set')
                          ->times()
                          ->andReturnNull();

    return $cacheBackendInterface;
  }

  /**
   * @return \stdClass
   */
  public function cacheBackendReturnCacheObject() {
    $cache = new \stdClass();
    $cache->valid = TRUE;
    $cache->data = self::$cacheData;
    return $cache;
  }

  /**
   * @return null
   */
  public function cacheBackendReturnNull() {
    return NULL;
  }

  /**
   * @return \stdClass
   */
  public function cacheBackendReturnCacheObjectAll() {
    $cache = new \stdClass();
    $cache->valid = TRUE;
    $cache->data = self::$cacheDataAll;
    return $cache;
  }

  /**
   * Test invalid default
   */
  public function testInvalidFieldDefault() {
    $typeMapper = $this->getTypeMapper($this->getCacheBackendInterface());
    $default = $typeMapper->getFieldOptions('invalid');
    $this->assertEquals([], $default);
  }

  /**
   * Test invalid options with all set
   */
  public function testInvalidFieldDefaultAllSet() {
    $typeMapper = $this->getTypeMapper($this->getCacheBackendInterface(TRUE));
    $default = $typeMapper->getFieldOptions('invalid');
    $this->assertEquals(['none' => 'none'], $default);
  }

  /**
   * Test getting the default value for a field
   */
  public function testFieldDefault() {
    $typeMapper = $this->getTypeMapper($this->getCacheBackendInterface());
    $default = $typeMapper->getFieldOptions('text');
    $this->assertEquals(self::$cacheData['supports']['text'], $default);
  }

  /**
   * Test getting an array of form additions
   */
  public function testGetFormAdditions() {
    $typeMapper = $this->getTypeMapper($this->getCacheBackendInterface());
    $this->assertEquals(self::$formFieldArray,
                        $typeMapper->getFormAdditions('text'));
  }

  /**
   * Test supported field type
   */
  public function testSupportsFields() {
    $typeMapper = $this->getTypeMapper($this->getCacheBackendInterface());
    $this->assertTrue($typeMapper->supportsFields('text'));
  }

  /**
   * Test an unsupported field type
   */
  public function testNotSupportsFields() {
    $typeMapper = $this->getTypeMapper($this->getCacheBackendInterface());
    $this->assertFalse($typeMapper->supportsFields('beeblebrox'));
  }

  /**
   * Test having no cache and also an invalid field id
   */
  public function testNoCacheSupportsFieldsInvalid() {
    $typeMapper = $this->getTypeMapper($this->getCacheBackendInterface(FALSE,
                                                                       TRUE));
    $this->assertFalse($typeMapper->supportsFields('beeblebrox'));
  }

  /**
   * Test having no cache and also a valid field id
   */
  public function testNoCacheSupportsFields() {
    $typeMapper = $this->getTypeMapper($this->getCacheBackendInterface(FALSE,
                                                                       TRUE));
    $this->assertTrue($typeMapper->supportsFields('text'));
  }

  /**
   * Test building the cache
   */
  public function testNoCache() {
    $typeMapper = $this->getTypeMapper($this->getCacheBackendInterface(FALSE,
                                                                       TRUE));
    $this->assertEquals(self::$cacheData['supports']['text'],
                        $typeMapper->getFieldOptions('text'));
  }

  /**
   * Test having no cache and also an invalid field id
   */
  public function testNoCacheInvalidId() {
    $typeMapper = $this->getTypeMapper($this->getCacheBackendInterface(FALSE,
                                                                       TRUE));
    $this->assertEquals([], $typeMapper->getFieldOptions('donut_description'));
  }

  /**
   * Test setting the cache and then being unable to retrieve it
   *
   * @expectedException \Drupal\elastic_search\Exception\TypeMapperException
   * @expectedExceptionMessage Could not create cache
   */
  public function testBrokenCache() {
    $backend = \Mockery::mock(CacheBackendInterface::class);
    $nullCache = [$this, 'cacheBackendReturnNull'];
    $backend->shouldReceive('get')
            ->times()
            ->andReturnUsing($nullCache,
                             $nullCache);
    $backend->shouldReceive('set')
            ->times()
            ->andReturnNull();
    $typeMapper = $this->getTypeMapper($backend);
    $typeMapper->getFieldOptions('donut_description');
  }

  /**
   * Test building the cache
   */
  public function testNoCacheAndAll() {
    $backend = \Mockery::mock(CacheBackendInterface::class);
    $backend->shouldReceive('get')
            ->times()
            ->andReturnUsing([$this, 'cacheBackendReturnNull'],
                             [$this, 'cacheBackendReturnCacheObjectAll']);
    $backend->shouldReceive('set')
            ->times()
            ->andReturnNull();

    $fieldMapperManager = \Mockery::mock(FieldMapperManager::class);
    $fieldMapperManager->shouldReceive('createInstance')
                       ->times()
                       ->andReturnUsing([
                                          $this,
                                          'fieldMapperCreateInstanceCallback',
                                        ]);

    $definitions = [
      'none' => [
        'id'       => 'none',
        'label'    => \Mockery::mock(TranslatableMarkup::class)->makePartial(),
        'class'    => 'Drupal\\elastic_search\\Plugin\\FieldMapper\\None',
        'provider' => 'elastic_search',
      ],
      'text' => [
        'id'       => 'text',
        'label'    => \Mockery::mock(TranslatableMarkup::class)->makePartial(),
        'class'    => 'Drupal\\elastic_search\\Plugin\\FieldMapper\\Text',
        'provider' => 'elastic_search',
      ],
    ];
    $fieldMapperManager->shouldReceive('getDefinitions')
                       ->times()
                       ->andReturn($definitions);

    $logger = \Mockery::mock(LoggerInterface::class);
    $eventDispatcher = \Mockery::mock(EventDispatcherInterface::class);
    $eventDispatcher->shouldReceive('dispatch')->times()->andReturnUsing([
                                                                           $this,
                                                                           'eventDispatchDispatchCallback',
                                                                         ]);

    $typeMapper = new TypeMapper($fieldMapperManager,
                                 $backend,
                                 $logger,
                                 $eventDispatcher);

    $this->assertArraySubset(['none' => 'none'],
                             $typeMapper->getFieldOptions('all'));
    $this->assertArraySubset(['none' => 'none'],
                             $typeMapper->getFieldOptions('paralysis'));
    $this->assertArraySubset(['none' => 'none'],
                             $typeMapper->getFieldOptions('text'));

  }

}
