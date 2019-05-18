<?php

namespace Drupal\Tests\m4032404\Unit\EventSubscriber;

use Drupal\m4032404\EventSubscriber\M4032404EventSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Tests for M4032404EventSubscriber.
 *
 * @coversDefaultClass \Drupal\m4032404\EventSubscriber\M4032404EventSubscriber
 *
 * @group m4032404
 */
class M4032404EventSubscriberTest extends UnitTestCase {

  /**
   * The event.
   *
   * @var \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent
   */
  protected $event;

  /**
   * The admin context.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\Routing\AdminContext
   */
  protected $adminContext;

  /**
   * The current user.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The config factory.
   *
   * @var \PHPUnit_Framework_MockObject_MockBuilder|\Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminContext = $this->getMockBuilder('\Drupal\Core\Routing\AdminContext')
      ->disableOriginalConstructor()
      ->getMock();
    $this->currentUser = $this->getMockBuilder('\Drupal\Core\Session\AccountProxy')
      ->disableOriginalConstructor()
      ->getMock();

    $kernel = $this->getMock('\Symfony\Component\HttpKernel\HttpKernelInterface');
    $request = new Request();

    $this->event = new GetResponseForExceptionEvent($kernel, $request, HttpKernelInterface::MASTER_REQUEST, new AccessDeniedHttpException());
  }

  /**
   * Tests event handling for all routes.
   *
   * @covers ::onAccessDeniedException
   */
  public function testHandleAll() {

    $this->configFactory = $this->getConfigFactoryStub([
      'm4032404.settings' => [
        'admin_only' => FALSE,
      ],
    ]);

    $this->adminContext->method('isAdminRoute')
      ->willReturn(FALSE);

    $subscriber = new M4032404EventSubscriber($this->configFactory, $this->adminContext, $this->currentUser);
    $subscriber->onAccessDeniedException($this->event);

    $e = $this->event->getException();

    $this->assertTrue($e instanceof NotFoundHttpException);

  }

  /**
   * Tests event handling for admin only routes when admin route.
   *
   * @covers ::onAccessDeniedException
   */
  public function testAdminOnlySuccess() {
    $this->configFactory = $this->getConfigFactoryStub([
      'm4032404.settings' => [
        'admin_only' => TRUE,
      ],
    ]);

    $this->adminContext->method('isAdminRoute')
      ->willReturn(TRUE);

    $subscriber = new M4032404EventSubscriber($this->configFactory, $this->adminContext, $this->currentUser);
    $subscriber->onAccessDeniedException($this->event);

    $e = $this->event->getException();

    $this->assertTrue($e instanceof NotFoundHttpException);
  }

  /**
   * Tests event handling for admin only routes when not admin route.
   *
   * @covers ::onAccessDeniedException
   */
  public function testAdminOnlyFailure() {
    $this->configFactory = $this->getConfigFactoryStub([
      'm4032404.settings' => [
        'admin_only' => TRUE,
      ],
    ]);

    $this->adminContext->method('isAdminRoute')
      ->willReturn(FALSE);

    $subscriber = new M4032404EventSubscriber($this->configFactory, $this->adminContext, $this->currentUser);
    $subscriber->onAccessDeniedException($this->event);

    $e = $this->event->getException();

    $this->assertTrue($e instanceof AccessDeniedHttpException);
  }

}
