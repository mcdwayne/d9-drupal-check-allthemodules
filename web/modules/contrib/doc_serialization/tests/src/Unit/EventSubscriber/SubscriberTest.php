<?php

namespace Drupal\Tests\doc_serialization\Unit\EventSubscriber;

use Drupal\Tests\UnitTestCase;
use Drupal\doc_serialization\EventSubscriber\Subscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Tests the DOC serialization event subscriber.
 *
 * @group doc_serialization
 *
 * @coversDefaultClass \Drupal\doc_serialization\EventSubscriber\Subscriber
 */
class SubscriberTest extends UnitTestCase {

  /**
   * @covers ::onKernelRequest
   */
  public function testOnKernelRequest() {
    // Both doc and docx should be set.
    /** @var \Symfony\Component\HttpFoundation\Request $request */
    $request = $this->prophesize(Request::class);
    $request->setFormat('docx', ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'])->shouldBeCalled();
    $event = $this->prophesize(GetResponseEvent::class);
    $event->getRequest()->willReturn($request->reveal());
    $subscriber = new Subscriber();
    $subscriber->onKernelRequest($event->reveal());
  }

}
