<?php

namespace Drupal\Tests\advban\Unit;

use Drupal\advban\AdvbanMiddleware;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @coversDefaultClass \Drupal\advban\AdvbanMiddleware
 * @group Advban
 */
class AdvbanMiddlewareTest extends UnitTestCase {

  /**
   * The mocked wrapped kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $kernel;

  /**
   * The mocked ban IP manager.
   *
   * @var \Drupal\advban\AdvbanIpManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $banManager;

  /**
   * The tested ban middleware.
   *
   * @var \Drupal\advban\AdvbanMiddleware
   */
  protected $banMiddleware;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->kernel = $this->getMock('Symfony\Component\HttpKernel\HttpKernelInterface');
    $this->banManager = $this->getMock('Drupal\advban\AdvbanIpManagerInterface');
    $this->banMiddleware = new AdvbanMiddleware($this->kernel, $this->banManager);
  }

  /**
   * Tests a banned IP.
   */
  public function testBannedIp() {
    $banned_ip = '17.0.0.2';
    $this->banManager->expects($this->once())
      ->method('isBanned')
      ->with($banned_ip)
      ->willReturn(TRUE);

    $request = Request::create('/test-path');
    $request->server->set('REMOTE_ADDR', $banned_ip);
    $expected_response = new Response(403);
    $this->kernel->expects($this->once())
      ->method('handle')
      ->with($request, HttpKernelInterface::MASTER_REQUEST, TRUE)
      ->willReturn($expected_response);

    $response = $this->banMiddleware->handle($request);

    $this->assertSame($expected_response, $response);
  }

  /**
   * Tests an unbanned IP.
   */
  public function testUnbannedIp() {
    $unbanned_ip = '18.0.0.2';
    $this->banManager->expects($this->once())
      ->method('isBanned')
      ->with($unbanned_ip)
      ->willReturn(FALSE);

    $request = Request::create('/test-path');
    $request->server->set('REMOTE_ADDR', $unbanned_ip);
    $expected_response = new Response(200);
    $this->kernel->expects($this->once())
      ->method('handle')
      ->with($request, HttpKernelInterface::MASTER_REQUEST, TRUE)
      ->willReturn($expected_response);

    $response = $this->banMiddleware->handle($request);

    $this->assertSame($expected_response, $response);
  }

}
