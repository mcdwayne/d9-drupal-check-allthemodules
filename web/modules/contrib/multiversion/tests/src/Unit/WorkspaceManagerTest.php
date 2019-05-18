<?php

namespace Drupal\Tests\multiversion\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;
use Drupal\multiversion\Workspace\WorkspaceManager;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\multiversion\Workspace\WorkspaceManager
 * @group multiversion
 */
class WorkspaceManagerTest extends UnitTestCase {

  /**
   * The entities under test.
   *
   * @var array
   */
  protected $entities;

  /**
   * The entities values.
   *
   * @var array
   */
  protected $values;

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $container;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityTypeManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $requestStack;

  /**
   * The ID of the type of the entity under test.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity type used for testing.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityType;

  /**
   * The workspace negotiators.
   *
   * @var array
   */
  protected $workspaceNegotiators;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $currentUser;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $logger;

  /**
   * The block manager.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $block_manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityTypeId = 'workspace';
    $first_machine_name = $this->randomMachineName();
    $second_machine_name = $this->randomMachineName();
    $this->values = [['machine_name' => $first_machine_name], ['machine_name' => $second_machine_name]];

    $this->entityType = $this->getMock('Drupal\multiversion\Entity\WorkspaceInterface');
    $this->entityTypeManager = $this->getMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $this->currentUser = $this->getMock('Drupal\Core\Session\AccountProxyInterface');
    $this->logger = $this->getMock('Psr\Log\LoggerInterface');
    $this->block_manager = $this->getMockBuilder('Drupal\Core\Block\BlockManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->entityTypeManager->expects($this->any())
      ->method('getDefinition')
      ->with($this->entityTypeId)
      ->will($this->returnValue($this->entityType));
    $this->requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityTypeManager);
    $container->set('request_stack', $this->requestStack);
    $container->setParameter('workspace.default', 1);
    \Drupal::setContainer($container);

    foreach ($this->values as $value) {
      $entity = $this->getMockBuilder('Drupal\multiversion\Entity\Workspace')
        ->disableOriginalConstructor()
        ->getMock();
      $entity->expects($this->any())
        ->method('create')
        ->with($value)
        ->will($this->returnValue($this->entityType));
      $this->entities[] = $entity;
    }

    $this->workspaceNegotiators[] = [$this->getMock('Drupal\multiversion\Workspace\DefaultWorkspaceNegotiator')];
    $session_workspace_negotiator = $this->getMockBuilder('Drupal\multiversion\Workspace\SessionWorkspaceNegotiator')
      ->disableOriginalConstructor()
      ->getMock();
    $this->workspaceNegotiators[] = [$session_workspace_negotiator];
  }

  /**
   * Tests the addNegotiator() method.
   */
  public function testAddNegotiator() {
    $workspace_manager = new WorkspaceManager($this->requestStack, $this->entityTypeManager, $this->currentUser, $this->logger);
    $workspace_manager->addNegotiator($this->workspaceNegotiators[0][0], 0);
    $workspace_manager->addNegotiator($this->workspaceNegotiators[1][0], 1);

    $property = new \ReflectionProperty('Drupal\multiversion\Workspace\WorkspaceManager', 'negotiators');
    $property->setAccessible(TRUE);

    $this->assertSame($this->workspaceNegotiators, $property->getValue($workspace_manager));
  }

  /**
   * Tests the load() method.
   */
  public function testLoad() {
    $storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('load')
      ->with(1)
      ->will($this->returnValue($this->entities[0]));

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->will($this->returnValue($storage));

    $workspace_manager = new WorkspaceManager($this->requestStack, $this->entityTypeManager, $this->currentUser, $this->logger);
    $entity = $workspace_manager->load(1);

    $this->assertSame($this->entities[0], $entity);
  }

  /**
   * Tests the loadMultiple() method.
   */
  public function testLoadMultiple() {
    $ids = [1,2];
    $storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->with($ids)
      ->will($this->returnValue($this->entities));

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with($this->entityTypeId)
      ->will($this->returnValue($storage));

    $workspace_manager = new WorkspaceManager($this->requestStack, $this->entityTypeManager, $this->currentUser, $this->logger);
    $entities = $workspace_manager->loadMultiple($ids);

    $this->assertSame($this->entities, $entities);
  }

  /**
   * Tests that setActiveWorkspace() sets the workspace on the negotiator.
   */
  public function testSetActiveWorkspace() {
    // Create the request we will use.
    $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    // Create the workspace that we will set.
    $workspace = $this->getMockBuilder('Drupal\multiversion\Entity\Workspace')
      ->disableOriginalConstructor()
      ->getMock();
    $workspace->expects($this->any())->method("isPublished")->willReturn(TRUE);
    $workspace->expects($this->any())->method("access")->willReturn(TRUE);

    // Spy on the negotiator and stub the applies and persist methods.
    $negotiator = $this->prophesize('Drupal\multiversion\Workspace\DefaultWorkspaceNegotiator');
    $negotiator->applies(Argument::any())->willReturn(TRUE);
    $negotiator->persist(Argument::any())->will(function(){ return $this; });

    // Create the workspace manager.
    $workspace_manager = new WorkspaceManager($this->requestStack, $this->entityTypeManager, $this->currentUser, $this->logger);
    $workspace_manager->addNegotiator($negotiator->reveal(), 1);

    // Execute the code under test.
    $workspace_manager->setActiveWorkspace($workspace);

    // Ensure persist with the workspace was called on the negotiator.
    $negotiator->persist($workspace)->shouldHaveBeenCalled();
  }

  /**
   * Tests that getActiveWorkspace() gets from the negotiator.
   */
  public function testGetActiveWorkspace() {
    $workspace_id = '123';

    // Create the request we will use.
    $request = $this->getMock('Symfony\Component\HttpFoundation\Request');
    $this->requestStack->method('getCurrentRequest')->willReturn($request);

    // Create the workspace that we will get.
    $workspace = $this->getMockBuilder('Drupal\multiversion\Entity\Workspace')
      ->disableOriginalConstructor()
      ->getMock();

    // Create the negotiator and stub the applies and getWorkspaceId methods.
    $negotiator = $this->getMock('Drupal\multiversion\Workspace\DefaultWorkspaceNegotiator');
    $negotiator->method('applies')->willReturn(TRUE);
    $negotiator->method('getWorkspaceId')->willReturn($workspace_id);

    // Create the storage and stub the load method.
    $storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $storage->method('load')->with($workspace_id)->willReturn($workspace);

    // Stub the entity manager to return $storage.
    $this->entityTypeManager->method('getStorage')
      ->with($this->entityTypeId)
      ->willReturn($storage);

    // Create the workspace manager with the negotiator.
    $workspace_manager = new WorkspaceManager($this->requestStack, $this->entityTypeManager, $this->currentUser, $this->logger);
    $workspace_manager->addNegotiator($negotiator, 1);

    // Execute the code under test.
    $active_workspace = $workspace_manager->getActiveWorkspace();

    // Ensure value is the workspace we stubbed.
    $this->assertSame($workspace, $active_workspace);
  }

  /**
   * Tests the getSortedNegotiators() method.
   */
  public function testGetSortedNegotiators() {
    $workspace_manager = new WorkspaceManager($this->requestStack, $this->entityTypeManager, $this->currentUser, $this->logger);
    $workspace_manager->addNegotiator($this->workspaceNegotiators[0][0], 1);
    $workspace_manager->addNegotiator($this->workspaceNegotiators[1][0], 3);

    $method = new \ReflectionMethod('Drupal\multiversion\Workspace\WorkspaceManager', 'getSortedNegotiators');
    $method->setAccessible(TRUE);

    $sorted_negotiators = new \ReflectionProperty('Drupal\multiversion\Workspace\WorkspaceManager', 'sortedNegotiators');
    $sorted_negotiators->setAccessible(TRUE);
    $sorted_negotiators_value = $sorted_negotiators->getValue($workspace_manager);

    $negotiators = new \ReflectionProperty('Drupal\multiversion\Workspace\WorkspaceManager', 'negotiators');
    $negotiators->setAccessible(TRUE);
    $negotiators_value = $negotiators->getValue($workspace_manager);

    if (!isset($sorted_negotiators_value)) {
      // Sort the negotiators according to priority.
      krsort($negotiators_value);
      // Merge nested negotiators from $negotiators_value into
      // $sorted_negotiators_value.
      $sorted_negotiators_value = [];
      foreach ($negotiators_value as $builders) {
        $sorted_negotiators_value = array_merge($sorted_negotiators_value, $builders);
      }
    }
    $this->assertSame($sorted_negotiators_value, $method->invoke($workspace_manager));
  }

}
