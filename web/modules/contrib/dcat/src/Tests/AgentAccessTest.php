<?php

namespace Drupal\dcat\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\dcat\Entity\DcatAgent;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group dcat
 */
class AgentAccessTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['dcat'];

  /**
   * Access handler.
   *
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $accessHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->accessHandler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('dcat_agent');
  }

  /**
   * Tests the Agent view access.
   */
  public function testView() {
    $agent_published = DcatAgent::create([
      'name' => $this->randomMachineName(8),
      'uid' => \Drupal::currentUser()->id(),
      'status' => 1,
    ]);
    $agent_unpublished = DcatAgent::create([
      'name' => $this->randomMachineName(8),
      'uid' => \Drupal::currentUser()->id(),
      'status' => 0,
    ]);

    $this->assertFalse($this->accessHandler->access($agent_published, 'view'), "Published Agent can't be viewed");
    $this->assertFalse($this->accessHandler->access($agent_unpublished, 'view'), "Unpublished Agent can't be viewed");

    $user1 = $this->drupalCreateUser();
    $this->assertFalse($this->accessHandler->access($agent_published, 'view', $user1), "Published Agent can't be viewed");
    $this->assertFalse($this->accessHandler->access($agent_unpublished, 'view', $user1), "Unpublished Agent can't be viewed");

    $user2 = $this->drupalCreateUser(['view published agent entities']);
    $this->assertTrue($this->accessHandler->access($agent_published, 'view', $user2), "Published Agent can be viewed");
    $this->assertFalse($this->accessHandler->access($agent_unpublished, 'view', $user2), "Unpublished Agent can't be viewed");

    $user3 = $this->drupalCreateUser(['view unpublished agent entities']);
    $this->assertFalse($this->accessHandler->access($agent_published, 'view', $user3), "Published Agent can't be viewed");
    $this->assertTrue($this->accessHandler->access($agent_unpublished, 'view', $user3), "Unpublished Agent can be viewed");
  }

  /**
   * Tests the Agent create access.
   */
  public function testCreate() {
    $this->assertFalse($this->accessHandler->createAccess(), "Agent can't be created");

    $user1 = $this->drupalCreateUser();
    $this->assertFalse($this->accessHandler->createAccess(NULL, $user1), "Agent can't be created");

    $user2 = $this->drupalCreateUser(['add agent entities']);
    $this->assertTrue($this->accessHandler->createAccess(NULL, $user2), 'Agent can be created');
  }

  /**
   * Tests the Agent update access.
   */
  public function testUpdate() {
    $agent = DcatAgent::create([
      'name' => $this->randomMachineName(8),
      'uid' => \Drupal::currentUser()->id(),
    ]);

    $this->assertFalse($this->accessHandler->access($agent, 'update'), "Agent can't be updated");

    $user1 = $this->drupalCreateUser();
    $this->assertFalse($this->accessHandler->access($agent, 'update', $user1), "Agent can't be updated");

    $user2 = $this->drupalCreateUser(['edit agent entities']);
    $this->assertTrue($this->accessHandler->access($agent, 'update', $user2), "Agent can be updated");
  }

  /**
   * Tests the Agent delete access.
   */
  public function testDelete() {
    $agent = DcatAgent::create([
      'name' => $this->randomMachineName(8),
      'uid' => \Drupal::currentUser()->id(),
    ]);

    $this->assertFalse($this->accessHandler->access($agent, 'delete'), "Agent can't be deleted");

    $user1 = $this->drupalCreateUser();
    $this->assertFalse($this->accessHandler->access($agent, 'delete', $user1), "Agent can't be deleted");

    $user2 = $this->drupalCreateUser(['delete agent entities']);
    $this->assertTrue($this->accessHandler->access($agent, 'delete', $user2), "Agent can be deleted");
  }

}
