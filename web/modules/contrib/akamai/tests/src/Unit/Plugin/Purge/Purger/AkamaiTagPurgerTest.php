<?php

namespace Drupal\Tests\akamai\Unit\Plugin\Purge\Purger;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\akamai\Kernel\EventSubscriber\MockSubscriber;
use Drupal\akamai\Event\AkamaiPurgeEvents;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * @coversDefaultClass \Drupal\akamai\Plugin\Purge\Purger\AkamaiTagPurger
 *
 * @group Akamai
 */
class AkamaiTagPurgerTest extends UnitTestCase {

  /**
   * Tests purge creation event dispatch.
   */
  public function testPurgeCreationEvent() {

    $purger = $this->getMockBuilder('Drupal\akamai\Plugin\Purge\Purger\AkamaiTagPurger')
      ->disableOriginalConstructor()
      ->setMethods(NULL)
      ->getMock();

    $container = new ContainerBuilder();

    $formatter = $this->getMockBuilder('Drupal\akamai\Helper\CacheTagFormatter')->getMock();
    $formatter->method('format')
      ->willReturn('foo');

    $container->set('akamai.helper.cachetagformatter', $formatter);
    \Drupal::setContainer($container);

    $formatter = $this->getMockBuilder('Drupal\akamai\Helper\CacheTagFormatter')->getMock();

    $client = $this->getMockBuilder('Drupal\akamai\Plugin\Client\AkamaiClientV3')
      ->disableOriginalConstructor()
      ->setMethods(['setType', 'purgeTags'])
      ->getMock();

    $reflection = new \ReflectionClass($purger);
    $reflection_property = $reflection->getProperty('client');
    $reflection_property->setAccessible(TRUE);
    $reflection_property->setValue($purger, $client);

    // Setup the mock event subscriber.
    $subscriber = new MockSubscriber();
    $event_dispatcher = new EventDispatcher();
    $event_dispatcher->addListener(AkamaiPurgeEvents::PURGE_CREATION, [$subscriber, 'onPurgeCreation']);

    $reflection_property = $reflection->getProperty('eventDispatcher');
    $reflection_property->setAccessible(TRUE);
    $reflection_property->setValue($purger, $event_dispatcher);

    // Create stub for response class.
    $invalidation = $this->getMockBuilder('Drupal\purge\Plugin\Purge\Invalidation\TagInvalidation')
      ->disableOriginalConstructor()
      ->getMock();
    $invalidation->method('getExpression')
      ->willReturn('foo');

    $purger->invalidate([$invalidation]);

    $this->assertEquals(['foo', 'on_purge_creation'], $subscriber->event->data);
  }

}
