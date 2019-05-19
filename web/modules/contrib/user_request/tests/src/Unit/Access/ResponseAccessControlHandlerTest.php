<?php

namespace Drupal\Tests\user_request\Unit\Access;

/**
 * @coversDefaultClass \Drupal\user_request\Access\ResponseAccessControlHandler
 * @group user_request
 */
class ResponseAccessControlHandlerTest extends AccessControlHandlerTest {

  const RESPONSE_TYPE = 'user_request_response';

  /**
   * The request's owner.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $requestOwner;

  /**
   * Some other account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $nonRequestOwner;

  /**
   * The response's owner.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $responseOwner;

  /**
   * The response whose access will be tested.
   *
   * @var \Drupal\user_request\Entiy\ResponseInterface
   */
  protected $response;

  public function testWithoutAnyPermission() {
    $allowed = $this->accessControlHandler->access($this->response, 'view', 
      $this->requestOwner);
    $this->assertFalse($allowed);
  }

  public function testResponseOwnerWithPermission() {
    $perm = 'view received user_request_response';
    $this->permissions[$perm] = TRUE;
    $allowed = $this->accessControlHandler->access($this->response, 'view', 
      $this->responseOwner);
    $this->assertFalse($allowed);
  }

  public function testPermissionPerEntityType() {
    $perm = 'view received user_request_response';
    $this->permissions[$perm] = TRUE;
    $allowed = $this->accessControlHandler->access($this->response, 'view', 
      $this->requestOwner);
    $this->assertTrue($allowed);
  }

  public function testPermissionOtherOperation() {
    $perm = 'view received user_request';
    $this->permissions[$perm] = TRUE;
    $allowed = $this->accessControlHandler->access($this->response, 'update', 
      $this->requestOwner);
    $this->assertFalse($allowed);
  }

  public function testPermissionPerBundleType() {
    $perm = 'view received ' . self::RESPONSE_TYPE . ' user_request_response';
    $this->permissions[$perm] = TRUE;
    $allowed = $this->accessControlHandler->access($this->response, 'view', 
      $this->requestOwner);
    $this->assertTrue($allowed);
  }

  public function testPermissionOtherBundleType() {
    $perm = 'view received other_bundle user_request_response';
    $this->permissions[$perm] = TRUE;
    $allowed = $this->accessControlHandler->access($this->response, 'view', 
      $this->requestOwner);
    $this->assertFalse($allowed);
  }

  public function testHasPermissionButIsNotRequestOwner() {
    $perm = 'view received user_request_response';
    $this->permissions[$perm] = TRUE;
    $allowed = $this->accessControlHandler->access($this->response, 'view', 
      $this->nonRequestOwner);
    $this->assertFalse($allowed);
  }

  public function testPermissionCheckedByParentClass() {
    $perm = 'view any ' . self::RESPONSE_TYPE . ' user_request_response';
    $this->permissions[$perm] = TRUE;
    $allowed = $this->accessControlHandler->access($this->response, 'view', 
      $this->nonRequestOwner);
    $this->assertTrue($allowed);
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Creates accounts.
    $this->nonRequestOwner = $this->mockAccount(1);
    $this->requestOwner = $this->mockAccount(2);
    $this->responseOwner = $this->mockAccount(3);

    // Mocks the request and its response.
    $request = $this->mockRequest([
      'owner' => $this->requestOwner,
    ]);
    $this->response = $this->mockResponse([
      'owner' => $this->responseOwner,
      'request' => $request,
    ]);

    // Instantiates the access control handler.
    $this->accessControlHandler = $this->createAccessControlHandler(
      '\Drupal\user_request\Access\ResponseAccessControlHandler');
  }

}
