<?php

namespace Drupal\Tests\user_request\Unit\Access;

/**
 * @coversDefaultClass \Drupal\user_request\Access\ResponseFormAccessCheck
 * @group user_request
 */
class ResponseFormAccessCheckTest extends FormAccessCheckTest {

  const RESPONSE_TRANSITION = 'approve';

  /**
   * The request's recipient's account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $recipient;

  /**
   * Account of non recipient user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $nonRecipient;

  /**
   * A request without allowed state transitions.
   *
   * @var \Drupal\user_request\Entity\RequestInterface
   */
  protected $requestNoTransition;

  /**
   * A request with allowed state transitions (no response transition).
   *
   * @var \Drupal\user_request\Entity\RequestInterface
   */
  protected $requestWithTransition;

  /**
   * A request with allowed response transition.
   *
   * @var \Drupal\user_request\Entity\RequestInterface
   */
  protected $requestWithResponseTransition;

  public function testRecipientWithResponseTransition() {
    $route_match = $this->mockRouteMatch([
      'user_request' => $this->requestWithResponseTransition,
    ]);
    $result = $this->formAccessCheck->access($this->route, $route_match, 
      $this->recipient);
    $this->assertTrue($result->isAllowed());
  }

  public function testRecipientWithoutTransition() {
    $route_match = $this->mockRouteMatch([
      'user_request' => $this->requestNoTransition,
    ]);
    $result = $this->formAccessCheck->access($this->route, $route_match, 
      $this->recipient);
    $this->assertFalse($result->isAllowed());
  }

  public function testRecipientNonResponseTransition() {
    $route_match = $this->mockRouteMatch([
      'user_request' => $this->requestWithTransition,
    ]);
    $result = $this->formAccessCheck->access($this->route, $route_match, 
      $this->recipient);
    $this->assertFalse($result->isAllowed());
  }

  public function testNonRecipientWithResponseTransition() {
    $route_match = $this->mockRouteMatch([
      'user_request' => $this->requestWithResponseTransition,
    ]);
    $result = $this->formAccessCheck->access($this->route, $route_match, 
      $this->nonRecipient);
    $this->assertFalse($result->isAllowed());
  }

  public function testNonRecipientWithoutTransition() {
    $route_match = $this->mockRouteMatch([
      'user_request' => $this->requestNoTransition,
    ]);
    $result = $this->formAccessCheck->access($this->route, $route_match, 
      $this->nonRecipient);
    $this->assertFalse($result->isAllowed());
  }

  public function testNonRecipientNonResponseTransition() {
    $route_match = $this->mockRouteMatch([
      'user_request' => $this->requestWithTransition,
    ]);
    $result = $this->formAccessCheck->access($this->route, $route_match, 
      $this->nonRecipient);
    $this->assertFalse($result->isAllowed());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Creates the object to be tested.
    $this->formAccessCheck = $this->createFormAccessCheck(
      '\Drupal\user_request\Access\ResponseFormAccessCheck');

    // Mocks recipient and non recipient accounts.
    $this->recipient = $this->mockAccount(1);
    $this->nonRecipient = $this->mockAccount(2);

    // Mocks states for requests.
    $state_no_transition = $this->mockRequestState();
    $state_with_transition = $this->mockRequestState([
      'transitions' => [
        $this->mockStateTransition(),
      ],
    ]);
    $state_with_response_transition = $this->mockRequestState([
      'transitions' => [
        $this->mockStateTransition(),
        $this->mockStateTransition(['id' => self::RESPONSE_TRANSITION]),
      ],
    ]);

    // Mocks the request type to return proper response transitions.
    $request_type = $this->mockRequestType([
      'response_transitions' => [self::RESPONSE_TRANSITION],
    ]);

    // Mocks requests with mocked states.
    $this->requestNoTransition = $this->mockRequest([
      'user_request_type' => $request_type,
      'state' => $state_no_transition,
      'recipients' => [$this->recipient],
    ]);
    $this->requestWithTransition = $this->mockRequest([
      'user_request_type' => $request_type,
      'state' => $state_with_transition,
      'recipients' => [$this->recipient],
    ]);
    $this->requestWithResponseTransition = $this->mockRequest([
      'user_request_type' => $request_type,
      'state' => $state_with_response_transition,
      'recipients' => [$this->recipient],
    ]);
  }

}
