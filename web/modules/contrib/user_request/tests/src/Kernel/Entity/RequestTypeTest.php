<?php

namespace Drupal\Tests\user_request\Kernel\Entity;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\user_request\Entity\RequestType;

/**
 * @coverDefaultClass \Drupal\user_request\Entity\RequestType
 * @group user_request
 */
class RequestTypeTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user_request'];

  protected $entity;

  public function testSetAndGetWorkflow() {
    $this->entity->setWorkflow('crazy_workflow');
    $workflow = $this->entity->getWorkflow();
    $this->assertEquals('crazy_workflow', $workflow);
  }

  public function testSetAndGetResponseType() {
    $this->entity->setResponseType('good_response');
    $response_type = $this->entity->getResponseType();
    $this->assertEquals('good_response', $response_type);
  }

  public function testSetAndGetResponseTransitions() {
    $transitions = [
      'approve',
      'cancel',
    ];
    $this->entity->setResponseTransitions($transitions);
    $returned_transitions = $this->entity->getResponseTransitions();
    $this->assertCount(2, $returned_transitions);
    $this->assertContains('approve', $returned_transitions);
    $this->assertContains('cancel', $returned_transitions);
  }

  public function testIsResponseTransition() {
    // Sets response transitions.
    $this->entity->set('response_transitions', [
      'approve',
      'reject',
    ]);
  
    // Checks transitions. 
    $this->assertTrue($this->entity->isResponseTransition('approve'));
    $this->assertTrue($this->entity->isResponseTransition('reject'));
    $this->assertFalse($this->entity->isResponseTransition('cancel'));
  }

  public function testGetDeletedResponseTransition() {
    $this->assertEmpty($this->entity->getDeletedResponseTransition());
    $this->entity->set('deleted_response_transition', 'reset');
    $this->assertEquals('reset', $this->entity->getDeletedResponseTransition());
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Creates a request type for the tests.
    $this->entity = RequestType::create([
      'id' => 'test_type',
    ]);
  }

}
