<?php

namespace Drupal\Tests\pxe\Unit;

use Drupal\pxe\XsdValidationExceptionSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use TurnerLabs\ValidatingXmlEncoder\Exception\XsdValidationException;

/**
 * Tests for the XsdValidationExceptionSubscriber class.
 *
 * @group pxe
 *
 * @coversDefaultClass \Drupal\pxe\XsdValidationExceptionSubscriber
 */
class XsdValidationExceptionSubscriberTest extends UnitTestCase {

  /**
   * Test the onException method.
   *
   * @covers ::onException
   */
  public function testOnException() {
    // Setup a simple DOMDocument.
    $document = new \DOMDocument();
    $child = $document->createElement('root');
    $document->appendChild($child);

    $exception = $this->getMockBuilder(XsdValidationException::class)
      ->disableOriginalConstructor()
      ->setMethods(['getInvalidXmlDocument'])
      ->getMock();
    $exception->method('getInvalidXmlDocument')
      ->willReturn($document);

    $event = $this->getMockBuilder(GetResponseForExceptionEvent::class)
      ->disableOriginalConstructor()
      ->setMethods(['getException'])
      ->getMock();
    $event->method('getException')
      ->willReturn($exception);

    $subscriber = new XsdValidationExceptionSubscriber();
    $subscriber->onException($event);
    $response = $event->getResponse();
    $this->assertNotNull($response);
    $this->assertContains('XSD validation failed', $response->getContent());
    $this->assertContains('root', $response->getContent());
  }

}
