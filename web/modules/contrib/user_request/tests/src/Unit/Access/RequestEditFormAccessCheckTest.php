<?php

namespace Drupal\Tests\user_request\Unit\Access;

/**
 * @coversDefaultClass \Drupal\user_request\Access\RequestEditFormAccessCheck
 * @group user_request
 */
class RequestEditFormAccessCheckTest extends FormAccessCheckTest {

  /**
   * A mocked route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The request's current state.
   *
   * @var \Drupal\state_machine\Plugin\Field\FieldType\StateItemInterface
   */
  protected $state;

  /**
   * The request whose access will be verified.
   *
   * @var \Drupal\user_request\Entity\RequestInterface
   */
  protected $request;

  /**
   * The request's owner account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $owner;

  /**
   * Non owner's account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $nonOwner;

  public function testNoPermission() {
    $result = $this->formAccessCheck->access($this->route, $this->routeMatch, $this->nonOwner);
    $this->assertFalse($result->isAllowed());
  }

  public function testOwnerNoPermission() {
    $result = $this->formAccessCheck->access($this->route, $this->routeMatch, $this->owner);
    $this->assertFalse($result->isAllowed());
  }

  public function testPermissionForAnyEntityInState() {
    $this->addPermission('update any ' . $this->state->getId() . ' user_request');
    $result = $this->formAccessCheck->access($this->route, $this->routeMatch, $this->nonOwner);
    $this->assertTrue($result->isAllowed());
  }

  public function testPermissionForAnyEntityNotInState() {
    $this->addPermission('update any other_state user_request');
    $result = $this->formAccessCheck->access($this->route, $this->routeMatch, $this->nonOwner);
    $this->assertFalse($result->isAllowed());
  }

  public function testPermissionForAnyEntityOfBundleInState() {
    $this->addPermission('update any ' . $this->state->getId() . ' '
      . $this->request->bundle() . ' user_request');
    $result = $this->formAccessCheck->access($this->route, $this->routeMatch, $this->nonOwner);
    $this->assertTrue($result->isAllowed());
  }

  public function testPermissionForAnyEntityOfOtherBundleInState() {
    $this->addPermission('update any ' . $this->state->getId()
      . ' other_bundle user_request');
    $result = $this->formAccessCheck->access($this->route, $this->routeMatch, $this->nonOwner);
    $this->assertFalse($result->isAllowed());
  }

  public function testPermissionForAnyEntityOfBundleNotInState() {
    $this->addPermission('update any other_state '. $this->request->bundle() 
      . ' user_request');
    $result = $this->formAccessCheck->access($this->route, $this->routeMatch, $this->nonOwner);
    $this->assertFalse($result->isAllowed());
  }

  public function testPermissionForOwnEntityInState() {
    $this->addPermission('update own ' . $this->state->getId() . ' user_request');
    $result = $this->formAccessCheck->access($this->route, $this->routeMatch, $this->owner);
    $this->assertTrue($result->isAllowed());
  }

  public function testPermissionForOwnEntityNotInState() {
    $this->addPermission('update own other_state user_request');
    $result = $this->formAccessCheck->access($this->route, $this->routeMatch, $this->owner);
    $this->assertFalse($result->isAllowed());
  }

  public function testPermissionForNotOwnEntityInState() {
    $this->addPermission('update own ' . $this->state->getId() . ' user_request');
    $result = $this->formAccessCheck->access($this->route, $this->routeMatch, $this->nonOwner);
    $this->assertFalse($result->isAllowed());
  }

  public function testPermissionForOwnEntityOfBundleInState() {
    $this->addPermission('update own ' . $this->state->getId() . ' '
      . $this->request->bundle() . ' user_request');
    $result = $this->formAccessCheck->access($this->route, $this->routeMatch, $this->owner);
    $this->assertTrue($result->isAllowed());
  }

  public function testPermissionForOwnEntityOfBundleNotInState() {
    $this->addPermission('update own other_state ' . $this->request->bundle()  
      . ' user_request');
    $result = $this->formAccessCheck->access($this->route, $this->routeMatch, $this->owner);
    $this->assertFalse($result->isAllowed());
  }

  public function testPermissionForNotOwnEntityOfBundleInState() {
    $this->addPermission('update own ' . $this->state->getId() . ' '
      . $this->request->bundle() . ' user_request');
    $result = $this->formAccessCheck->access($this->route, $this->routeMatch, $this->nonOwner);
    $this->assertFalse($result->isAllowed());
  }

  public function testPermissionForOwnEntityOfOtherBundleInState() {
    $this->addPermission('update own ' . $this->state->getId()
      . 'other_bundle user_request');
    $result = $this->formAccessCheck->access($this->route, $this->routeMatch, $this->owner);
    $this->assertFalse($result->isAllowed());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Creates the accounts.
    $this->nonOwner = $this->mockAccount(1);
    $this->owner = $this->mockAccount(2);

    // Creates the request.
    $this->state = $this->mockRequestState();
    $this->request = $this->mockRequest([
      'owner' => $this->owner,
      'state' => $this->state
    ]);

    // Mocks the route match.
    $this->routeMatch = $this->mockRouteMatch([
      'user_request' => $this->request,
    ]);

    // Instantiates the object to be tested.
    $this->formAccessCheck = $this->createFormAccessCheck(
      '\Drupal\user_request\Access\RequestEditFormAccessCheck');
  }

}
