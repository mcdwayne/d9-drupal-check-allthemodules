<?php

namespace Drupal\Tests\crm_core_user_sync\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\crm_core_contact\IndividualInterface;
use Drupal\crm_core_user_sync\CrmCoreUserSyncRelationInterface;
use Drupal\crm_core_user_sync\EventSubscriber\RequestSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Test description.
 *
 * @group crm_core_user_sync
 */
class RequestSubscriberTest extends UnitTestCase {

  /**
   * Tests nothing happens for Anonymous.
   */
  public function testRequestSubscriberAnonymous() {
    $current_user = $this->createMock(AccountProxyInterface::class);
    $current_user->expects($this->once())
      ->method('isAuthenticated')
      ->willReturn(FALSE);

    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory
      ->expects($this->never())
      ->method('get');

    $relationService = $this->createMock(CrmCoreUserSyncRelationInterface::class);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager
      ->expects($this->never())
      ->method('getStorage');

    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('/', 'GET');

    $subscriber = new RequestSubscriber($current_user, $configFactory, $relationService, $entityTypeManager);
    $event = new GetResponseEvent($kernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST);
    $subscriber->onKernelRequest($event);
    // Nothing to assert as we should exit immediately. Current user expectation
    // will fail the test if something...
  }

  /**
   * Tests nothing loaded for user without related contact.
   */
  public function testRequestSubscriberAuthenticatedWithoutContact() {
    $current_user = $this->createMock(AccountProxyInterface::class);
    $current_user->expects($this->once())
      ->method('isAuthenticated')
      ->willReturn(TRUE);
    $current_user
      ->expects($this->once())
      ->method('id')
      ->willReturn('101');

    $config = $this->getMockBuilder(ImmutableConfig::class)
      ->disableOriginalConstructor()
      ->getMock();
    $config
      ->expects($this->once())
      ->method('get')
      ->with('contact_load')
      ->willReturn(TRUE);

    $config_name = 'crm_core_user_sync.settings';
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory
      ->expects($this->once())
      ->method('get')
      ->with($config_name)
      ->willReturn($config);

    $relationService = $this->createMock(CrmCoreUserSyncRelationInterface::class);
    $relationService
      ->expects($this->once())
      ->method('getIndividualIdFromUserId')
      ->willReturn(FALSE);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager
      ->expects($this->never())
      ->method('getStorage');

    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('/', 'GET');

    $subscriber = new RequestSubscriber($current_user, $configFactory, $relationService, $entityTypeManager);
    $event = new GetResponseEvent($kernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST);
    $subscriber->onKernelRequest($event);
    // Nothing to assert. Entity type manager expectations will the test.
  }

  /**
   * Tests contact loaded for the user with related contact.
   */
  public function testRequestSubscriberAuthenticatedWithContact() {
    $current_user = $this->createMock(AccountProxyInterface::class);
    $current_user->expects($this->once())
      ->method('isAuthenticated')
      ->willReturn(TRUE);
    $current_user
      ->expects($this->once())
      ->method('id')
      ->willReturn('101');

    $account = $this->createMock(AccountInterface::class);
    $current_user
      ->expects($this->once())
      ->method('getAccount')
      ->willReturn($account);

    $current_user
      ->expects($this->at(0))
      ->method('setAccount')
      ->willReturnReference($account);

    $config = $this->getMockBuilder(ImmutableConfig::class)
      ->disableOriginalConstructor()
      ->getMock();
    $config
      ->expects($this->once())
      ->method('get')
      ->with('contact_load')
      ->willReturn(TRUE);

    $config_name = 'crm_core_user_sync.settings';
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $configFactory
      ->expects($this->once())
      ->method('get')
      ->with($config_name)
      ->willReturn($config);

    $individualId = 101;
    $individual = $this->createMock(IndividualInterface::class);

    $relationService = $this->createMock(CrmCoreUserSyncRelationInterface::class);
    $relationService
      ->expects($this->once())
      ->method('getIndividualIdFromUserId')
      ->willReturn($individualId);

    $entityStorage = $this->createMock(EntityStorageInterface::class);
    $entityStorage
      ->expects($this->once())
      ->method('load')
      ->with($individualId)
      ->willReturn($individual);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager
      ->expects($this->once())
      ->method('getStorage')
      ->with('crm_core_individual')
      ->willReturn($entityStorage);

    $kernel = $this->prophesize(HttpKernelInterface::class);
    $request = Request::create('/', 'GET');

    $subscriber = new RequestSubscriber($current_user, $configFactory, $relationService, $entityTypeManager);
    $event = new GetResponseEvent($kernel->reveal(), $request, HttpKernelInterface::MASTER_REQUEST);
    $subscriber->onKernelRequest($event);
    $this->assertEquals($individual, $account->crm_core['contact'], 'Related contact was loaded');
  }

}
