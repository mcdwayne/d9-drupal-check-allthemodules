<?php

namespace Drupal\Tests\amazon_sns\Unit;

use Drupal\amazon_sns\Controller\NotificationController;
use Drupal\amazon_sns\Event\MessageEventDispatcher;
use Drupal\Tests\UnitTestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the notification controller and error handling.
 *
 * @group amazon_sns
 */
class NotificationControllerTest extends UnitTestCase {
  use PlainTextMessageTrait;

  /**
   * Test normal message processing.
   */
  public function testReceive() {
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\amazon_sns\Event\MessageEventDispatcher $dispatcher */
    $dispatcher = $this->getMockBuilder(MessageEventDispatcher::class)
      ->disableOriginalConstructor()
      ->getMock();
    $dispatcher->expects($this->once())->method('dispatch');

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface $logger */
    $logger = $this->getMockBuilder(LoggerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $logger->expects($this->never())->method($this->anything());

    $controller = new NotificationController($dispatcher, $logger);

    $content = file_get_contents(__DIR__ . '/../../fixtures/plain-text-message.json');
    $request = Request::create('http://example.com/_amazon-sns/notify', 'POST', [], [], [], $this->getFixtureServer(), $this->getFixtureBody());
    $response = $controller->receive($request);
    $this->assertEquals(200, $response->getStatusCode());
  }

  /**
   * Test error handling when a required header is missing.
   */
  public function testMissingRequiredHeaders() {
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\amazon_sns\Event\MessageEventDispatcher $dispatcher */
    $dispatcher = $this->getMockBuilder(MessageEventDispatcher::class)
      ->disableOriginalConstructor()
      ->getMock();
    $dispatcher->expects($this->never())->method('dispatch');

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface $logger */
    $logger = $this->getMockBuilder(LoggerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $logger->expects($this->once())->method('log');

    $controller = new NotificationController($dispatcher, $logger);

    $server = $this->getFixtureServer();
    unset($server['HTTP_X_AMZ_SNS_MESSAGE_TYPE']);
    $request = Request::create('http://example.com/_amazon-sns/notify', 'POST', [], [], [], $server, $this->getFixtureBody());
    $response = $controller->receive($request);
    $this->assertEquals(400, $response->getStatusCode());
    $this->assertEquals('SNS message type header not provided', $response->getContent());
  }

  /**
   * Test when a message fails signature validation.
   */
  public function testInvalidSignature() {
    /** @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\amazon_sns\Event\MessageEventDispatcher $dispatcher */
    $dispatcher = $this->getMockBuilder(MessageEventDispatcher::class)
      ->disableOriginalConstructor()
      ->getMock();
    $dispatcher->expects($this->never())->method('dispatch');

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface $logger */
    $logger = $this->getMockBuilder(LoggerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $logger->expects($this->once())->method('log');

    $controller = new NotificationController($dispatcher, $logger);

    $content = $this->getFixtureBody();
    $content = json_decode($content);
    $content->Signature .= '-invalid';
    $content = json_encode($content);
    $request = Request::create('http://example.com/_amazon-sns/notify', 'POST', [], [], [], $this->getFixtureServer(), $content);
    $response = $controller->receive($request);
    $this->assertEquals(400, $response->getStatusCode());
    $this->assertEquals('The message signature is invalid.', $response->getContent());
  }

}
