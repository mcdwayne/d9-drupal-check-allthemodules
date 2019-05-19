<?php

namespace Drupal\Tests\user_request\Unit\EventSubscriber;

use Drupal\user_request\EventSubscriber\DeleteResponseSubscriber;

/**
 * @ocversDefaultClass \Drupal\user_request\EventSubscriber
 * @group user_request
 */
class DeleteResponseSubscriberTest extends EventSubscriberTest {

  const DELETE_TRANSITION = 'reset';

  /**
   * Event for transition configured for deletion.
   *
   * @var \Drupal\state_machine\Event\WorkflowTransitionEvent
   */
  protected $deleteTransitionEvent;

  /**
   * Event for transition not configured for deletion.
   *
   * @var \Drupal\state_machine\Event\WorkflowTransitionEvent
   */
  protected $notDeleteTransitionEvent;

  /**
   * The request whose state is changing.
   *
   * @var \Drupal\user_request\Entity\RequestInterface
   */
  protected $request;

  /**
   * The response to be removed.
   *
   * @var \Drupal\user_request\Entity\ResponseInterface
   */
  protected $response;

  public function testSubscribedEvents() {
    $subscribed_events = DeleteResponseSubscriber::getSubscribedEvents();
    $this->assertArrayHasKey('user_request.pre_transition', $subscribed_events);
    $this->assertArrayHasKey('user_request.post_transition', $subscribed_events);
  }

  public function testRemoveOnDeleteTransition() {
    // Sets expectations.
    $this->request
      ->expects($this->once())
      ->method('removeResponse');
    $this->request
      ->expects($this->never())
      ->method('save');
    $this->response
      ->expects($this->never())
      ->method('delete');

    // Executes the subscriber.
    $this->eventSubscriber->removeResponse($this->deleteTransitionEvent);
  }

  public function testRemoveOnOtherTransition() {
    // Sets expectations.
    $this->request
      ->expects($this->never())
      ->method('removeResponse');
    $this->response
      ->expects($this->never())
      ->method('delete');

    // Executes the subscriber.
    $this->eventSubscriber->removeResponse($this->notDeleteTransitionEvent);
  }

  public function testDeleteResponse() {
    // Sets expectations.
    $this->response
      ->expects($this->once())
      ->method('delete');

    // Executes the subscriber.
    $this->eventSubscriber->deleteResponseEntity($this->deleteTransitionEvent);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Mocks the request type.
    $request_type = $this->mockRequestType([
      'deleted_response_transition' => self::DELETE_TRANSITION,
    ]);

    // Mocks the response.
    $this->response = $this->mockResponse();

    // Mocks the request and the transitions.
    $this->request = $this->mockRequest([
      'user_request_type' => $request_type,
      'removed_response' => $this->response,
    ]);
    $delete_transition = $this->mockStateTransition([
      'id' => self::DELETE_TRANSITION,
    ]);
    $not_delete_transition = $this->mockStateTransition();

    // Mocks the transition events.
    $this->deleteTransitionEvent = $this->mockTransitionEvent(
      $delete_transition, $this->request);
    $this->notDeleteTransitionEvent = $this->mockTransitionEvent(
      $not_delete_transition, $this->request);

    // Instantiates the event subscriber.
    $this->eventSubscriber = new DeleteResponseSubscriber();
  }

}
