<?php

namespace Drupal\Tests\switches\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\Core\Executable\ExecutableManagerInterface;
use Drupal\switches\Entity\SwitchEntity;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Test the SwitchEntity class.
 *
 * @group switches
 *
 * @coversDefaultClass \Drupal\switches\Entity\SwitchEntity
 */
class SwitchEntityTest extends UnitTestCase {

  /**
   * The SwitchEntity instance being tested.
   *
   * @var \Drupal\switches\Entity\SwitchEntity
   */
  protected $switchEntity;

  /**
   * Test container with mocked services.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * Mocked entity type repository service.
   *
   * @var \Drupal\Core\Entity\EntityTypeRepositoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeRepository;

  /**
   * Mocked entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * Mocked entity storage handler service.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityStorageHandler;

  /**
   * Mocked condition plugin manager service.
   *
   * @var \Drupal\Core\Executable\ExecutableManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $conditionPluginManager;

  /**
   * {@inheritdoc}
   */
  public function setup() {

    // Prepare the container for testing.
    $this->container = new ContainerBuilder();

    // Build our service mocks.
    $this->entityTypeRepository = $this->prophesize(EntityTypeRepositoryInterface::class);
    $this->entityTypeRepository->getEntityTypeFromClass(Argument::type('string'))
      ->willReturn('config');
    $this->entityStorageHandler = $this->prophesize(EntityStorageInterface::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeManager->getStorage(Argument::type('string'))
      ->willReturn($this->entityStorageHandler->reveal());
    $this->conditionPluginManager = $this->prophesize(ExecutableManagerInterface::class);

    // Expose the service mocks into the container.
    $this->container->set('entity_type.repository', $this->entityTypeRepository->reveal());
    $this->container->set('entity_type.manager', $this->entityTypeManager->reveal());
    $this->container->set('plugin.manager.condition', $this->conditionPluginManager->reveal());

    // Point Drupal to our mock container.
    \Drupal::setContainer($this->container);
  }

  /**
   * The getDescription method properly returns a set value.
   *
   * @covers ::getDescription
   */
  public function testGetDescription() {
    $this->switchEntity = new SwitchEntity([
      'description' => 'My test description',
    ], 'switch');

    $this->assertEquals('My test description',
      $this->switchEntity->getDescription(), 'The configured description was returned correctly.');
  }

  /**
   * Tests the getActivationConditions method.
   *
   * @covers ::getActivationConditions
   */
  public function testGetActivationConditions() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Tests the getActivationCondition method.
   *
   * @covers ::getActivationCondition
   */
  public function testGetActivationCondition() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Tests the setActivationConditionConfig method.
   *
   * @covers ::setActivationConditionConfig
   */
  public function testSetActivationConditionConfig() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Tests the getActivationConditionsConfig method.
   *
   * @covers ::getActivationConditionsConfig
   */
  public function testGetActivationConditionsConfig() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

  /**
   * Tests the getActivationStatus method.
   *
   * @covers ::getActivationStatus
   */
  public function testGetActivationStatus() {
    $this->markTestIncomplete('This test has not been implemented yet.');
  }

}
