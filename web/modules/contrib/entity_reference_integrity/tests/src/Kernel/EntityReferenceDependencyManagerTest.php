<?php

namespace Drupal\Tests\entity_reference_integrity\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Test the EntityReferenceDependencyManager.
 *
 * @coversDefaultClass \Drupal\entity_reference_integrity\EntityReferenceDependencyManager
 *
 * @group entity_reference_integrity
 */
class EntityReferenceDependencyManagerTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'node',
    'user',
    'entity_reference_integrity',
  ];

  /**
   * The entity dependency manager.
   *
   * @var \Drupal\entity_reference_integrity\EntityReferenceDependencyManagerInterface
   */
  protected $dependencyManager;

  /**
   * A test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * A test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $testNode;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installSchema('system', 'sequences');
    $this->installSchema('node', 'node_access');

    $this->dependencyManager = $this->container->get('entity_reference_integrity.dependency_manager');

    $this->testUser = User::create([
      'name' => 'Gerald',
    ]);
    $this->testUser->save();

    $this->testNode = Node::create([
      'title' => 'foo',
      'type' => 'bar',
    ]);
    $this->testNode->save();
  }

  /**
   * Test the test user as the author of the test node.
   */
  protected function setUserAsNodeAuthor() {
    $this->testNode->uid = $this->testUser->id();
    $this->testNode->save();
  }

  /**
   * Test the reference check method.
   *
   * @covers ::hasDependents
   */
  public function testHasDependents() {
    $this->assertFalse($this->dependencyManager->hasDependents($this->testUser));
    $this->setUserAsNodeAuthor();
    $this->assertTrue($this->dependencyManager->hasDependents($this->testUser));
  }

  /**
   * Test the reference list method.
   *
   * @covers ::getDependentEntityIds
   */
  public function testGetDependentEntityIds() {
    $this->assertEquals([], $this->dependencyManager->getDependentEntityIds($this->testUser));
    $this->setUserAsNodeAuthor();
    $this->assertEquals($this->testNode->id(), $this->dependencyManager->getDependentEntityIds($this->testUser)['node'][0]);
  }

  /**
   * Test the reference list method.
   *
   * @covers ::getDependentEntities
   */
  public function testGetDependentEntities() {
    $this->assertEquals([], $this->dependencyManager->getDependentEntities($this->testUser));
    $this->setUserAsNodeAuthor();
    $this->assertEquals($this->testNode->id(), $this->dependencyManager->getDependentEntities($this->testUser)['node'][0]->id());
  }

}
