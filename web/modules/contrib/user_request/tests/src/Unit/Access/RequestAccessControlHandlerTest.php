<?php

namespace Drupal\Tests\user_request\Unit\Access;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\Container;

/**
 * @coversDefaultClass \Drupal\user_request\Access\RequestAccessControlHandler
 * @group user_request
 */
class RequestAccessControlHandlerTest extends AccessControlHandlerTest {

  const REQUEST_TYPE = 'user_request';

  /**
   * Recipient's account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $recipient;

  /**
   * Non recipient's account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $nonRecipient;

  /**
   * Request entity whose access will be tested.
   *
   * @var \Drupal\user_request\Entity\RequestInterface
   */
  protected $request;

  public function testWithoutAnyPermission() {
    $allowed = $this->accessControlHandler->access($this->request, 'view', 
      $this->recipient);
    $this->assertFalse($allowed);
  }

  public function testPermissionPerEntityType() {
    $perm = 'view received user_request';
    $this->permissions[$perm] = TRUE;
    $allowed = $this->accessControlHandler->access($this->request, 'view', 
      $this->recipient);
    $this->assertTrue($allowed);
  }

  public function testPermissionOtherOperation() {
    $perm = 'view received user_request';
    $this->permissions[$perm] = TRUE;
    $allowed = $this->accessControlHandler->access($this->request, 'update', 
      $this->recipient);
    $this->assertFalse($allowed);
  }

  public function testPermissionPerBundleType() {
    $perm = 'view received ' . self::REQUEST_TYPE . ' user_request';
    $this->permissions[$perm] = TRUE;
    $allowed = $this->accessControlHandler->access($this->request, 'view', 
      $this->recipient);
    $this->assertTrue($allowed);
  }

  public function testPermissionOtherBundleType() {
    $perm = 'view received other_bundle user_request';
    $this->permissions[$perm] = TRUE;
    $allowed = $this->accessControlHandler->access($this->request, 'view', 
      $this->recipient);
    $this->assertFalse($allowed);
  }

  public function testPermissionToRespondPerEntityType() {
    $perm = 'respond user_request';
    $this->permissions[$perm] = TRUE;
    $allowed = $this->accessControlHandler->access($this->request, 'update', 
      $this->recipient);
    $this->assertTrue($allowed);
  }

  public function testPermissionToRespondPerBundleType() {
    $perm = 'respond ' . self::REQUEST_TYPE . ' user_request';
    $this->permissions[$perm] = TRUE;
    $allowed = $this->accessControlHandler->access($this->request, 'update', 
      $this->recipient);
    $this->assertTrue($allowed);
  }

  public function testHasPermissionButIsNotRecipient() {
    $perm = 'view received user_request';
    $this->permissions[$perm] = TRUE;
    $allowed = $this->accessControlHandler->access($this->request, 'view', 
      $this->nonRecipient);
    $this->assertFalse($allowed);
  }

  public function testPermissionCheckedByParentClass() {
    $perm = 'view any ' . self::REQUEST_TYPE . ' user_request';
    $this->permissions[$perm] = TRUE;
    $allowed = $this->accessControlHandler->access($this->request, 'view', 
      $this->nonRecipient);
    $this->assertTrue($allowed);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Creates accounts.
    $this->nonRecipient = $this->mockAccount(1);
    $this->recipient = $this->mockAccount(2);

    // Mocks a request.
    $this->request = $this->mockRequest([
      'type' => self::REQUEST_TYPE,
      'recipients' => [$this->recipient],
    ]);

    // Instantiates the access control handler.
    $this->accessControlHandler = $this->createAccessControlHandler(
      '\Drupal\user_request\Access\RequestAccessControlHandler');
  }

}
