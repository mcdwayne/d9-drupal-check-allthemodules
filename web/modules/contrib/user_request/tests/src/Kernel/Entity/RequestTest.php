<?php

namespace Drupal\Tests\user_request\Kernel\Entity;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\user_request\Entity\Request;
use Drupal\user_request\Entity\RequestType;
use Drupal\user_request\Entity\Response;

/**
 * @coversDefaultClass \Drupal\user_request\Entity\Request
 * @group user_request
 */
class RequestTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['state_machine', 'user', 'user_request'];

  protected $entity;

  public function testGetRequestType() {
    $request_type = $this->entity->getRequestType();
    $this->assertInstanceOf(RequestType::class, $request_type);
    $this->assertEquals('user_request', $request_type->id());
  }

  public function testRequestedBy() {
    // Creates user to be the request owner.
    $owner = $this->createUser();
    $this->entity->setOwner($owner);

    // Assertions.
    $requester = $this->entity->requestedBy();
    $this->assertEquals($owner->id(), $requester->id());
  }

  public function testSetAndGetRecipients() {
    // Sets recipients.
    $recipients = [
      $this->createUser(),
      $this->createUser(),
    ];
    $this->entity->setRecipients($recipients);

    // Gets recipients.
    $returned_recipients = $this->entity->getRecipients();

    // Checks returned value.
    $returned_ids = array_map(function ($r) {
      return $r->id();
    }, $returned_recipients);
    $this->assertCount(2, $returned_recipients);
    $this->assertContains($recipients[0]->id(), $returned_ids);
    $this->assertContains($recipients[1]->id(), $returned_ids);
  }

  public function testGetResponse() {
    // Initially, the response should be empty.
    $this->assertEmpty($this->entity->getResponse());

    // Sets a response.
    $response = Response::create([
      'id' => 123,
      'type' => 'user_request_response',
    ]);
    $this->entity->response = $response;

    // Tests getting the response.
    $returned_response = $this->entity->getResponse();
    $this->assertInstanceOf(Response::class, $returned_response);
    $this->assertEquals($response->id(), $returned_response->id());
  }

  public function testApplyTransition() {
    // Initially, the request is pending.
    $this->assertEquals('pending', $this->entity->state->value);

    // Cancels the request and checks the new state.
    $this->entity->applyTransition('cancel');
    $this->assertEquals('canceled', $this->entity->state->value);
  }

  public function testResponding() {
    // Initially, the request is pending and has no response.
    $this->assertEquals('pending', $this->entity->state->value);   
    $this->assertEmpty($this->entity->getResponse());

    // Responds the request.
    $response = Response::create([
      'id' => 222,
      'type' => 'user_request_response',
    ]);
    $this->entity->respond('approve', $response);

    // Checks current state and response.
    $this->assertEquals('approved', $this->entity->state->value);
    $this->assertEquals(222, $this->entity->response->entity->id());
  }

  public function testRespondingWithInvalidTransition() {
    $this->setExpectedException(\InvalidArgumentException::class);

    // Responds with a transition that was not configured for response.
    $response = Response::create([
      'id' => 1,
      'type' => 'user_request_response',
    ]);
    $this->entity->respond('cancel', $response);
  }

  public function testRespondingInvalidResponseType() {
    $this->setExpectedException(\InvalidArgumentException::class);

    // Responds the request with not allowed bundle.
    $response = Response::create([
      'id' => 222,
      'type' => 'not_allowed',
    ]);
    $this->entity->respond('approve', $response);
  }

  public function testRespondingAlreadyRespondedRequest() {
    $this->setExpectedException(\LogicException::class);

    // Adds a response to the request.
    $this->entity->response->entity = Response::create([
      'id' => 1,
      'type' => 'user_request_response',
    ]);

    // Tries to respond again.
    $response = Response::create([
      'id' => 2,
      'type' => 'user_request_response',
    ]);
    $this->entity->respond('approve', $response);
  }

  public function testRemoveResponse() {
    // Adds a response to the request.
    $this->entity->response->entity = Response::create([
      'id' => 456,
      'type' => 'user_request_response',
    ]);

    // Initially getRemovedResponse() should return no value.
    $this->assertEmpty($this->entity->getRemovedResponse());

    // Removes the response and checks the value.
    $this->entity->removeResponse();
    $this->assertEmpty($this->entity->response->entity);

    // Also checks the value returned from getRemovedResponse().
    $removed_response = $this->entity->getRemovedResponse();
    $this->assertInstanceOf(Response::class, $removed_response);
    $this->assertEquals(456, $removed_response->id());
  }

  public function testGetState() {
    // Initially, the request is pending.
    $this->assertEquals('pending', $this->entity->getState()->getId());

    // Changes the state and checks again.
    $this->entity->state->value = 'canceled';
    $this->assertEquals('canceled', $this->entity->getState()->getId());
  }

  public function testGetStateString() {
    // Initially, the request is pending.
    $this->assertEquals('pending', $this->entity->getStateString());

    // Changes the state and checks again.
    $this->entity->state->value = 'canceled';
    $this->assertEquals('canceled', $this->entity->getStateString());
  }

  public function testInState() {
    // Initially, the request is pending.
    $this->assertTrue($this->entity->inState('pending'));
    $this->assertFalse($this->entity->inState('canceled'));

    // Changes the state and checks again.
    $this->entity->state->value = 'canceled';
    $this->assertFalse($this->entity->inState('pending'));
    $this->assertTrue($this->entity->inState('canceled'));
  }

  public function testHasResponse() {
    // Initially, the request does not have a response.
    $this->assertFalse($this->entity->hasResponse());

    // Adds a response and checks again.
    $this->entity->response->entity = Response::create([
      'id' => 1,
      'type' => 'user_request_response',
    ]);
    $this->assertTrue($this->entity->hasResponse());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['user_request']);

    // Creates a request entity for the tests.
    $this->entity = Request::create([
      'type' => 'user_request',
    ]);
  }

}
