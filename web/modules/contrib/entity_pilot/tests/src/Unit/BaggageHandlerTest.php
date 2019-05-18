<?php

namespace Drupal\Tests\entity_pilot\Unit;

use Drupal\Core\Cache\Cache;
use Drupal\entity_pilot\BaggageHandler;
use Drupal\entity_pilot\Event\EntityPilotEvents;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests baggage handler.
 *
 * @coversDefaultClass \Drupal\entity_pilot\BaggageHandler
 * @group entity_pilot
 */
class BaggageHandlerTest extends UnitTestCase {

  /**
   * Baggage handler.
   *
   * @var \Drupal\entity_pilot\BaggageHandler
   */
  protected $baggageHandler;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\MemoryBackend|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cache;

  /**
   * Mock entity.
   *
   * @var \Drupal\node\NodeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entity;

  /**
   * Mock event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $dispatcher;

  /**
   * Sets up the test.
   */
  protected function setUp() {
    $this->entity = $this->createMock('\Drupal\node\NodeInterface');
    $this->entity->expects($this->any())
      ->method('getEntityTypeId')
      ->willReturn('node');
    $this->entity->expects($this->any())
      ->method('id')
      ->willReturn(1);
    $this->cache = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->entity->expects($this->any())
      ->method('uuid')
      ->willReturn('entity-uuid');
    $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
    $this->baggageHandler = new BaggageHandler($this->cache, $this->dispatcher);
  }

  /**
   * Tests reset().
   *
   * @covers ::reset
   * @covers ::__construct
   */
  public function testReset() {
    $cid = sprintf('ep__%s__%s', $this->entity->getEntityTypeId(), $this->entity->id());
    $this->cache->expects($this->exactly(2))
      ->method('get')
      ->with($cid)
      ->willReturn((object) ['data' => 'magic ponies']);
    // Cold static cache should trigger cache get call.
    $dependencies = $this->baggageHandler->calculateDependencies($this->entity);
    $this->assertEquals($dependencies, 'magic ponies');
    // Warmed static cache.
    $dependencies = $this->baggageHandler->calculateDependencies($this->entity);
    $this->assertEquals($dependencies, 'magic ponies');
    $this->baggageHandler->reset();
    // Cold static cache should trigger cache get call again.
    $dependencies = $this->baggageHandler->calculateDependencies($this->entity);
    $this->assertEquals($dependencies, 'magic ponies');
  }

  /**
   * Tests calculateDependencies().
   *
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
    $this->dispatcher->expects($this->atLeast(1))
      ->method('dispatch')
      ->with(EntityPilotEvents::CALCULATE_DEPENDENCIES)
      ->willReturnArgument(1);
    $dependant1 = $this->createMock('\Drupal\node\NodeInterface');
    $dependant1->expects($this->any())
      ->method('getEntityTypeId')
      ->willReturn('node');
    $dependant1->expects($this->any())
      ->method('id')
      ->willReturn(2);
    $dependant1->expects($this->any())
      ->method('uuid')
      ->willReturn('pony-uuid');
    $dependant2 = $this->createMock('\Drupal\node\NodeInterface');
    $dependant2->expects($this->any())
      ->method('getEntityTypeId')
      ->willReturn('node');
    $dependant2->expects($this->any())
      ->method('id')
      ->willReturn(3);
    $dependant2->expects($this->any())
      ->method('uuid')
      ->willReturn('fooey-uuid');
    $this->entity->expects($this->any())
      ->method('referencedEntities')
      ->willReturn([$dependant1, $dependant2]);
    $dependant1->expects($this->any())
      ->method('referencedEntities')
      ->willReturn([]);
    $dependant2->expects($this->any())
      ->method('referencedEntities')
      ->willReturn([]);
    $this->cache->expects($this->any())
      ->method('get')
      ->willReturn(FALSE);
    $this->cache->expects($this->any())
      ->method('set')
      ->willReturnCallback(function ($cid, $data, $ttl, $tags) use ($dependant1, $dependant2) {
        $expected = [
          'ep__node__2' => [
            'ep__node__2',
            [],
            Cache::PERMANENT,
            ['ep__node__2'],
          ],
          'ep__node__3' => [
            'ep__node__3',
            [],
            Cache::PERMANENT,
            ['ep__node__3'],
          ],
          'ep__node__1' => [
            'ep__node__1',
            [
              'pony-uuid' => $dependant1,
              'fooey-uuid' => $dependant2,
            ],
            Cache::PERMANENT,
            [
              'ep__node__1',
              'ep__node__2',
              'ep__node__3',
            ],
          ],
        ];
        $this->assertEquals($expected[$cid], func_get_args());
      });
    $dependencies = $this->baggageHandler->calculateDependencies($this->entity);
    $this->assertEquals($dependencies, [
      'pony-uuid' => $dependant1,
      'fooey-uuid' => $dependant2,
    ]);
    // Second call should use static.
    $this->baggageHandler->calculateDependencies($this->entity);
    // Clear the static cache.
    $this->baggageHandler->reset();
    $return = (object) ['data' => ['giant asthmatic pandas']];
    // Prime the cache.
    $this->cache->expects($this->any())
      ->method('get')
      ->willReturnCallback(function ($cid) use ($dependant1, $dependant2) {
        if ($cid == 'ep__node__1') {
          return [
            'pony-uuid' => $dependant1,
            'fooey-uuid' => $dependant2,
          ];
        }
        return FALSE;
      });
    $dependencies = $this->baggageHandler->calculateDependencies($this->entity);
    $this->assertEquals($dependencies, [
      'pony-uuid' => $dependant1,
      'fooey-uuid' => $dependant2,
    ]);
  }

  /**
   * Tests calculateDependencies() with a circular reference.
   *
   * @covers ::calculateDependencies
   */
  public function testCalculateCircularDependencies() {
    $this->dispatcher->expects($this->atLeast(1))
      ->method('dispatch')
      ->with(EntityPilotEvents::CALCULATE_DEPENDENCIES)
      ->willReturnArgument(1);
    $dependant1 = $this->createMock('\Drupal\node\NodeInterface');
    $dependant1->expects($this->any())
      ->method('getEntityTypeId')
      ->willReturn('node');
    $dependant1->expects($this->any())
      ->method('id')
      ->willReturn(2);
    $dependant1->expects($this->any())
      ->method('uuid')
      ->willReturn('pony-uuid');
    $dependant2 = $this->createMock('\Drupal\node\NodeInterface');
    $dependant2->expects($this->any())
      ->method('getEntityTypeId')
      ->willReturn('node');
    $dependant2->expects($this->any())
      ->method('id')
      ->willReturn(3);
    $dependant2->expects($this->any())
      ->method('uuid')
      ->willReturn('fooey-uuid');
    $this->entity->expects($this->any())
      ->method('referencedEntities')
      ->willReturn([$dependant1]);
    $dependant1->expects($this->any())
      ->method('referencedEntities')
      ->willReturn([$this->entity, $dependant2]);
    $dependant2->expects($this->any())
      ->method('referencedEntities')
      ->willReturn([$dependant1]);
    $this->cache->expects($this->any())
      ->method('set')
      ->willReturn(TRUE);
    $this->cache->expects($this->any())
      ->method('get')
      ->willReturn(FALSE);
    $dependencies = $this->baggageHandler->calculateDependencies($this->entity);
    $this->assertEquals([
      'pony-uuid' => $dependant1,
      'fooey-uuid' => $dependant2,
    ], $dependencies);
  }

}
