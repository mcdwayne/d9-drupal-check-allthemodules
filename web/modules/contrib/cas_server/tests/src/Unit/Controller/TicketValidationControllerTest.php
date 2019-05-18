<?php

/**
 * @file
 * Contains Drupal\Tests\cas_server\Unit\Controller\TicketValidationControllerTest.
 */

namespace Drupal\Tests\cas_server\Unit\Controller;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\cas_server\Unit\TestTicketValidationController;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\HandlerStack;

/**
 * TicketValidationController unit tests.
 *
 * @group cas_server
 *
 * @coversDefaultClass \Drupal\cas_server\Controller\TicketValidationController
 */
class TicketValidationControllerTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->ticketFactory = $this->getMockBuilder('Drupal\cas_server\Ticket\TicketFactory')
      ->disableOriginalConstructor()
      ->getMock();

    $this->pgt = $this->getMockBuilder('Drupal\cas_server\Ticket\ProxyGrantingTicket')
      ->disableOriginalConstructor()
      ->getMock();

    $this->ticketStore = $this->getMockBuilder('Drupal\cas_server\Ticket\TicketStorageInterface')
      ->disableOriginalConstructor()
      ->getMock();

  }


  /**
   * Test the proxyCallback method through the test class.
   *
   * @covers ::proxyCallback
   *
   * @dataProvider proxyCallbackSuccessDataProvider
   */
  public function testProxyCallbackSuccess($url, $ticket) {
    $mock = new MockHandler([
      new Response(200),
      new Response(200),
    ]);
    $handler = HandlerStack::create($mock);
    $client = new Client(['handler' => $handler]);

    $this->ticketFactory->expects($this->once())
      ->method('createProxyGrantingTicket')
      ->will($this->returnValue($this->pgt));

    $this->pgt->expects($this->once())
      ->method('getId')
      ->will($this->returnValue('thisisapgtid'));

    $ticket->expects($this->any())
      ->method('getProxyChain')
      ->will($this->returnValue(['foo']));

    $controller = new TestTicketValidationController($client, $this->ticketFactory, $this->ticketStore);
    $this->assertNotFalse($controller->callProxyCallback($url, $ticket));
  }


  /**
   * Data provider for testProxyCallbackSuccess
   */
  public function proxyCallbackSuccessDataProvider() {

    $urls = ['https://example.com', 'https://example.com/bar?q=foo'];
    $st = $this->getMockBuilder('Drupal\cas_server\Ticket\ServiceTicket')
               ->disableOriginalConstructor()
               ->getMock();
    $pt = $this->getMockBuilder('Drupal\cas_server\Ticket\ProxyTicket')
               ->disableOriginalConstructor()
               ->getMock();

    return [
      [$urls[0], $st],
      [$urls[1], $st],
      [$urls[0], $pt],
      [$urls[1], $pt],
    ];
  }

  /**
   * Test failure conditions for proxyCallback.
   *
   * @covers ::proxyCallback
   *
   * @dataProvider proxyCallbackFailureDataProvider
   */
  public function testProxyCallbackFailure($url, $client) {
    
    $this->ticketFactory->expects($this->any())
      ->method('createProxyGrantingTicket')
      ->will($this->returnValue($this->pgt));

    $this->pgt->expects($this->any())
      ->method('getId')
      ->will($this->returnValue('thisisapgtid'));

    $st = $this->getMockBuilder('Drupal\cas_server\Ticket\ServiceTicket')
               ->disableOriginalConstructor()
               ->getMock();

    $controller = new TestTicketValidationController($client, $this->ticketFactory, $this->ticketStore);
    $this->assertFalse($controller->callProxyCallback($url, $st));
    
  }

  /**
   * Data provider for testProxyCallbackFailure
   */
  public function proxyCallbackFailureDataProvider() {
    $urls = ['http://example.com', 'https://example.com'];

    $mock = new MockHandler([
      new TransferException(),
      new Response(200),
    ]);
    $handler = HandlerStack::create($mock);
    $client1 = new Client(['handler' => $handler]);

    $mock = new MockHandler([
      new Response(200),
      new TransferException(),
    ]);
    $handler = HandlerStack::create($mock);
    $client2 = new Client(['handler' => $handler]);

    return [
      [$urls[0], $client1],
      [$urls[1], $client2],
      [$urls[1], $client1],
    ];
  }
}
