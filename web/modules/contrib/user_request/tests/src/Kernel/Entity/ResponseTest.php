<?php

namespace Drupal\Tests\user_request\Kernel\Entity;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\user\Entity\User;
use Drupal\user_request\Entity\Request;
use Drupal\user_request\Entity\Response;
use Drupal\user_request\Entity\ResponseType;

/**
 * @coversDefaultClass \Drupal\user_request\Entity\Response
 * @group user_request
 */
class ResponseTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['state_machine', 'user', 'user_request'];

  protected $entity;

  public function testGetResponseType() {
    $response_type = $this->entity->getResponseType();
    $this->assertInstanceOf(ResponseType::class, $response_type);
    $this->assertEquals('user_request_response', $response_type->id());
  }

  public function testRespondedBy() {
    $owner = $this->createUser();
    $this->entity->setOwner($owner);
    $responded_by = $this->entity->respondedBy();
    $this->assertInstanceOf(User::class, $responded_by);
    $this->assertEquals($owner->id(), $responded_by->id());
  }

  public function testGetRequest() {
    // As this method uses a query, it is necessary to add the response to a 
    // request and save them.
    $this->entity->save();
    $request = Request::create([
      'type' => 'user_request',
      'response' => [
        'target_id' => $this->entity->id(),
      ],
    ]);
    $request->save();

    // Creates some other request to test conditions.
    Request::create([
      'type' => 'user_request',
    ])->save();

    // Checks if the method returns the correct request.
    $returned_request = $this->entity->getRequest();
    $this->assertInstanceOf(Request::class, $returned_request);
    $this->assertEquals($request->id(), $returned_request->id());
    $this->assertEquals($request->response->target_id, $this->entity->id());
  } 

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['user_request']);
    $this->installEntitySchema('user_request');
    $this->installEntitySchema('user_request_response');

    // Creates a request entity for the tests.
    $this->entity = Response::create([
      'type' => 'user_request_response',
    ]);
  }

}
