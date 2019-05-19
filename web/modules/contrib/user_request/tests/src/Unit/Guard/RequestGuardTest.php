<?php

namespace Drupal\Tests\user_request\Guard;

use Drupal\Core\Sessions\AccountInterface;
use Drupal\Tests\user_request\Unit\Access\AccessTest;
use Drupal\user_request\Guard\RequestGuard;

/**
 * @coversDefaultClass \Drupal\user_request\Guard\RequestGuard
 * @group user_request
 */
class RequestGuardTest extends AccessTest {

  /**
   * The request guard under test.
   *
   * @var \Drupa\user_request\Gusrd\RequestGuard
   */
  protected $guard;

  /**
   * A request state transition.
   *
   * @var \Drupal\state_machine\Plugin\Workflow\WorkflowTransition
   */
  protected $transition;

  /**
   * The request whose access will be checked.
   *
   * @var \Drupal\user_request\Entity\RequestInterface
   */
  protected $request;

  /**
   * The request owner's account.
   *
   * @var \Drupal\Core\Sessions\AccountInterface
   */
  protected $owner;

  /**
   * The response owner's account.
   *
   * @var \Drupal\Core\Sessions\AccountInterface
   */
  protected $responseOwner;

  /**
   * A recipient that did not respond the request.
   *
   * @var \Drupal\Core\Sessions\AccountInterface
   */
  protected $otherRecipient;

  /**
   * Account of some user not related to the request.
   *
   * @var \Drupal\Core\Sessions\AccountInterface
   */
  protected $otherAccount;

  public function testNoPermission() {
    $this->assertFalse($this->guard->checkPermissions($this->transition, $this->request, $this->owner));
    $this->assertFalse($this->guard->checkPermissions($this->transition, $this->request, $this->responseOwner));
    $this->assertFalse($this->guard->checkPermissions($this->transition, $this->request, $this->otherRecipient));
    $this->assertFalse($this->guard->checkPermissions($this->transition, $this->request, $this->otherAccount));
  }

  public function testPermissionForOtherBundle() {
    $this->addTransitionPermission('any', 'other_bundle');
    $this->assertFalse($this->guard->checkPermissions($this->transition, $this->request, $this->owner));
    $this->assertFalse($this->guard->checkPermissions($this->transition, $this->request, $this->responseOwner));
    $this->assertFalse($this->guard->checkPermissions($this->transition, $this->request, $this->otherRecipient));
    $this->assertFalse($this->guard->checkPermissions($this->transition, $this->request, $this->otherAccount));
  }

  public function testPermissionForOtherTransition() {
    $other_transition = $this->mockStateTransition();
    $this->addTransitionPermission('any');
    $this->assertFalse($this->guard->checkPermissions($other_transition, $this->request, $this->owner));
    $this->assertFalse($this->guard->checkPermissions($other_transition, $this->request, $this->responseOwner));
    $this->assertFalse($this->guard->checkPermissions($other_transition, $this->request, $this->otherRecipient));
    $this->assertFalse($this->guard->checkPermissions($other_transition, $this->request, $this->otherAccount));
  }

  public function testCheckPermissionForAnyRequest() {
    $this->addTransitionPermission();
    $this->assertTrue($this->guard->checkPermissions($this->transition, $this->request, $this->owner));
    $this->assertTrue($this->guard->checkPermissions($this->transition, $this->request, $this->responseOwner));
    $this->assertTrue($this->guard->checkPermissions($this->transition, $this->request, $this->otherRecipient));
    $this->assertTrue($this->guard->checkPermissions($this->transition, $this->request, $this->otherAccount));
  }

  public function testCheckPermissionForOwnRequest() {
    $this->addTransitionPermission('own');
    $this->assertTrue($this->guard->checkPermissions($this->transition, $this->request, $this->owner));
    $this->assertFalse($this->guard->checkPermissions($this->transition, $this->request, $this->responseOwner));
    $this->assertFalse($this->guard->checkPermissions($this->transition, $this->request, $this->otherRecipient));
    $this->assertFalse($this->guard->checkPermissions($this->transition, $this->request, $this->otherAccount));
  }

  public function testCheckPermissionForRespondedRequest() {
    $this->addTransitionPermission('responded');
    $this->assertFalse($this->guard->checkPermissions($this->transition, $this->request, $this->owner));
    $this->assertTrue($this->guard->checkPermissions($this->transition, $this->request, $this->responseOwner));
    $this->assertFalse($this->guard->checkPermissions($this->transition, $this->request, $this->otherRecipient));
    $this->assertFalse($this->guard->checkPermissions($this->transition, $this->request, $this->otherAccount));
  }

  public function testCheckPermissionForReceivedRequest() {
    $this->addTransitionPermission('received');
    $this->assertFalse($this->guard->checkPermissions($this->transition, $this->request, $this->owner));
    $this->assertTrue($this->guard->checkPermissions($this->transition, $this->request, $this->responseOwner));
    $this->assertTrue($this->guard->checkPermissions($this->transition, $this->request, $this->otherRecipient));
    $this->assertFalse($this->guard->checkPermissions($this->transition, $this->request, $this->otherAccount));
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Mocks accounts.
    $this->owner = $this->mockAccount(1);
    $this->responseOwner = $this->mockAccount(2);
    $this->otherRecipient = $this->mockAccount(3);
    $this->otherAccount = $this->mockAccount(4);

    // Mocks the request and its response.
    $response = $this->mockResponse([
      'owner' => $this->responseOwner,
    ]);
    $this->request = $this->mockRequest([
      'owner' => $this->owner,
      'response' => $response,
      'recipients' => [
        $this->responseOwner,
        $this->otherRecipient,
      ],
    ]);

    // Mocks a state transition.
    $this->transition = $this->mockStateTransition();

    // Instantiates a request guard to be tested.
    $this->guard = new RequestGuard();
  }

  protected function addTransitionPermission($type = 'any', $bundle = 'user_request') {
    $transition_id = $this->transition->getId();
    $permission = "transition_$transition_id $type $bundle user_request";
    $this->addPermission($permission);
  }

}
