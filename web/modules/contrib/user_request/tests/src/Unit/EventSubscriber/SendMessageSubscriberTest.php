<?php

namespace Drupal\Tests\user_request\Unit\EventSubscriber;

use Drupal\user_request\EventSubscriber\SendMessageSubscriber;

/**
 * @coversDefaultClass \Drupal\user_request\EventSubscriber\SendMessageSubscriber
 * @group user_request
 */
class SendMessageSubscriberTest extends EventSubscriberTest {

  // IDs of Sender messages.
  const TRANSITION_MESSAGE = 'user_request_transition';
  const SENT_MESSAGE = 'user_request_sent';
  const RECEIVED_MESSAGE = 'user_request_received';

  /**
   * An event subscriber without Sender service set.
   *
   * @var \Drupal\user_request\EventSubscriber\EventSubscriberInterface
   */
  protected $eventSubscriberNoSender;

  /**
   * Mocked Sender service.
   *
   * @var \Drupal\sender\SenderInterface
   */
  protected $sender;

  /**
   * The request.
   *
   * @var \Drupal\user_request\Entity\RequestInterface
   */
  protected $request;

  /**
   * A request without configured messages.
   *
   * @var \Drupal\user_request\Entity\RequestInterface
   */
  protected $requestNoMessages;

  /**
   * Transition event.
   *
   * @var \Drupal\state_machine\Event\WorkflowTransitionEvent
   */
  protected $transitionEvent;

  /**
   * Transition event for request with no configured messages.
   *
   * @var \Drupal\state_machine\Event\WorkflowTransitionEvent
   */
  protected $transitionEventNoMessages;

  /**
   * The request's owner.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $owner;

  /**
   * The request's recipients.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $recipients;

  public function testSubscribedEvents() {
    $events = SendMessageSubscriber::getSubscribedEvents();
    $this->assertCount(1, $events);
    $this->assertArrayHasKey('user_request.post_transition', $events);
  }

  public function testSendTransitionMessageNoSender() {
    $this->sender
      ->expects($this->never())
      ->method('send');
    $this->eventSubscriberNoSender
      ->sendTransitionMessage($this->transitionEvent);
  }

  public function testSendNewRequestMessagesNoSender() {
    $this->sender
      ->expects($this->never())
      ->method('send');
    $this->eventSubscriberNoSender
      ->sendNewRequestMessages($this->request);
  }

  public function testSendTransitionMessage() {
    // Excects sending the same message to the request's owner and the
    // recipients.
    $this->sender
      ->expects($this->once())
      ->method('send')
      ->with(
        $this->equalTo(self::TRANSITION_MESSAGE),
        $this->logicalAnd(
          $this->countOf(3),
          $this->contains($this->owner),
          $this->contains($this->recipients[0]),
          $this->contains($this->recipients[1])
        ),
        $this->logicalAnd(
          $this->arrayHasKey('user_request'),
          $this->contains($this->request)
        )
      );
    $this->eventSubscriber
      ->sendTransitionMessage($this->transitionEvent);
  }

  public function testSendTransitionMessageWithoutConfiguredMessages() {
    $this->sender
      ->expects($this->never())
      ->method('send');
    $this->eventSubscriber
      ->sendTransitionMessage($this->transitionEventNoMessages);
  }

  public function testSendNewRequestMessagesSendsOnlyTwoTimes() {
    $this->sender
      ->expects($this->exactly(2))
      ->method('send');
    $this->eventSubscriber
      ->sendNewRequestMessages($this->request);
  }

  public function testSendNewRequestMessagesSendsToOwner() {
    $this->sender
      ->expects($this->at(0))
      ->method('send')
      ->with( 
        $this->equalTo(self::SENT_MESSAGE),
        $this->equalTo($this->owner),
        $this->logicalAnd(
          $this->arrayHasKey('user_request'),
          $this->contains($this->request)
        )
      );
    $this->eventSubscriber
      ->sendNewRequestMessages($this->request);
  }

  public function testSendNewRequestMessagesSendsToRecipients() {
    $this->sender
      ->expects($this->at(1))
      ->method('send')
      ->with(
        $this->equalTo(self::RECEIVED_MESSAGE),
        $this->logicalAnd(
          $this->countOf(2),
          $this->contains($this->recipients[0]),
          $this->contains($this->recipients[1])
        ),
        $this->logicalAnd(
          $this->arrayHasKey('user_request'),
          $this->contains($this->request)
        )
      );
    $this->eventSubscriber
      ->sendNewRequestMessages($this->request);
  }

  public function testSendNewRequestMessagesWithoutConfiguredMessages() {
    $this->sender
      ->expects($this->never())
      ->method('send');
    $this->eventSubscriber
      ->sendNewRequestMessages($this->requestNoMessages);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Mocks the request type with messages configured.
    $transition = $this->mockStateTransition();
    $request_type = $this->mockRequestType([
      'messages' => [
        'request_sent' => self::SENT_MESSAGE,
        'request_received' => self::RECEIVED_MESSAGE,
        'transitions' => [
          $transition->getId() => self::TRANSITION_MESSAGE,
        ],
      ],
    ]);

    // Creates request's owner and recipients mocks.
    $this->owner = $this->mockUser();
    $this->recipients = [
      $this->mockUser(),
      $this->mockUser(),
    ];

    // Mocks the request.
    $this->request = $this->mockRequest([
      'user_request_type' => $request_type,
      'owner' => $this->owner,
      'recipients' => $this->recipients,
    ]);

    // Mocks the transition event.
    $this->transitionEvent = $this->mockTransitionEvent($transition,
      $this->request);

    // Mocks a request and transition event without configured messages.
    $this->requestNoMessages = $this->mockRequest([
      'owner' => $this->owner,
      'recipients' => $this->recipients,
    ]);
    $this->transitionEventNoMessages = $this->mockTransitionEvent($transition,
      $this->requestNoMessages);

    // Mocks the Sender service.
    $this->sender = $this->mockSender();

    // Instantiates the event subscriber.
    $this->eventSubscriberNoSender = new SendMessageSubscriber();
    $this->eventSubscriber = new SendMessageSubscriber();
    $this->eventSubscriber->setSender($this->sender);
  }

  protected function mockSender() {
    return $this->getMock('\Drupal\sender\SenderInterface');
  }

}
