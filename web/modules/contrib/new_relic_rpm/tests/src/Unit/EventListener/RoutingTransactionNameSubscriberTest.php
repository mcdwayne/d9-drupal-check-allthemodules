<?php

namespace Drupal\Tests\new_relic_rpm\Unit\EventListener;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\new_relic_rpm\EventSubscriber\RoutingTransactionNameSubscriber;
use Drupal\node\NodeTypeInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * @coversDefaultClass \Drupal\new_relic_rpm\EventSubscriber\RoutingTransactionNameSubscriber
 * @group new_relic_rpm
 */
class RoutingTransactionNameSubscriberTest extends UnitTestCase {

  /**
   * @covers ::addTransactionNamesToRoutes
   */
  public function testSetsTransactionNameForAllRoutes() {
    $collection = new RouteCollection();
    $collection->add('foo', new Route('/foo'));
    $event = new RouteBuildEvent($collection);
    $subscriber = new RoutingTransactionNameSubscriber();
    $subscriber->addTransactionNamesToRoutes($event);

    $actualName = $collection->get('foo')->getDefault('_transaction_name');
    $this->assertEquals('foo', $actualName);
  }

  /**
   * @covers ::addTransactionNamesToRoutes
   */
  public function testSetsTransactionCallbackOnDynamicRoutes() {
    $collection = new RouteCollection();
    $collection->add('node.add', new Route('/node/add'));
    $event = new RouteBuildEvent($collection);
    $subscriber = new RoutingTransactionNameSubscriber();
    $subscriber->addTransactionNamesToRoutes($event);

    $actualName = $collection->get('node.add')->getDefault('_transaction_name');
    $actualCallback = $collection->get('node.add')->getDefault('_transaction_name_callback');
    $this->assertEquals('node.add', $actualName);
    $this->assertTrue(is_callable($actualCallback));
  }

  /**
   * @covers ::entityBundleRouteTransactionName
   */
  public function testEntityRouteTransactionName() {
    $entity = $this->prophesize(EntityInterface::class);
    $entity->bundle()->willReturn('bar');
    $attributes = [
      '_transaction_name' => 'entity.foo.canonical',
      'foo' => $entity->reveal(),
    ];
    $request = new Request([], [], $attributes);
    $actualName = RoutingTransactionNameSubscriber::entityBundleRouteTransactionName($request);
    $this->assertEquals('entity.foo.canonical:bar', $actualName);
  }

  /**
   * @covers ::nodeAddTransactionName
   */
  public function testNodeAddRouteTransactionName() {
    $node_type = $this->prophesize(NodeTypeInterface::class);
    $node_type->id()->willReturn('bar');
    $attributes = [
      '_transaction_name' => 'node.add',
      'node_type' => $node_type->reveal(),
    ];
    $request = new Request([], [], $attributes);
    $actualName = RoutingTransactionNameSubscriber::nodeAddTransactionName($request);
    $this->assertEquals('node.add:bar', $actualName);
  }

}
