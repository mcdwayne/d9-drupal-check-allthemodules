<?php

/**
 * @file
 * Contains \Drupal\Tests\retriever\Unit\Retriever\EntityTypeHandlerTest.
 */

namespace Drupal\Tests\retriever\Unit\Retriever;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\retriever\Retriever\EntityTypeHandler;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\retriever\Retriever\EntityTypeHandler
 *
 * @group Dependency Retriever
 */
class EntityTypeHandlerTest extends UnitTestCase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The subject under test.
   *
   * @var \Drupal\retriever\Retriever\EntityTypeHandler
   */
  protected $sut;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);

    $this->sut = new EntityTypeHandler($this->entityTypeManager->reveal());
  }

  /**
   * @covers ::getName
   * @covers ::__construct
   */
  public function testGetName() {
    $this->assertInternalType('string', $this->sut->getName());
  }

  /**
   * @covers ::knowsDependency
   * @covers ::extractHandlerInfo
   * @covers ::__construct
   *
   * @dataProvider getInvalidDependencyIds
   */
  public function testKnowsDependencyWithInvalidId($dependency_id) {
    $this->assertFalse($this->sut->knowsDependency($dependency_id));
  }

  /**
   * @covers ::retrieveDependency
   * @covers ::extractHandlerInfo
   * @covers ::__construct
   *
   * @dataProvider getInvalidDependencyIds
   *
   * @expectedException \BartFeenstra\DependencyRetriever\Exception\UnknownDependencyException
   */
  public function testRetrieveDependencyWithInvalidId($dependency_id) {
    $this->sut->retrieveDependency($dependency_id);
  }

  /**
   * Builds a list of invalid dependency IDs.
   *
   * @return array[]
   *   Values are arrays with a single string value.
   */
  public function getInvalidDependencyIds() {
    return [
      [''],
      ['.handlerType'],
      ['.handlerType.operation'],
      ['..operation'],
      ['..'],
      ['...'],
      ['entityTypeId..'],
      ['entityTypeId..operation'],
      ['entityTypeId.handlerType.'],
    ];
  }

  /**
   * @covers ::knowsDependency
   * @covers ::extractHandlerInfo
   * @covers ::__construct
   *
   * @dataProvider getKnows
   *
   * @param bool $knows
   */
  public function testKnowsDependencyWithoutOperation($knows) {
    $entity_type_id = 'foo';
    $handler_type = 'bar';
    $dependency_id = sprintf('%s.%s', $entity_type_id, $handler_type);

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->hasHandlerClass($handler_type, FALSE)->willReturn($knows);

    $this->entityTypeManager->getDefinition($entity_type_id)->willReturn($entity_type->reveal());

    $this->assertSame($knows, $this->sut->knowsDependency($dependency_id));
  }

  /**
   * @covers ::knowsDependency
   * @covers ::extractHandlerInfo
   * @covers ::__construct
   *
   * @dataProvider getKnows
   *
   * @param bool $knows
   */
  public function testKnowsDependencyWithOperation($knows) {
    $entity_type_id = 'foo';
    $handler_type = 'form';
    $operation = 'bar';
    $dependency_id = sprintf('%s.%s.%s', $entity_type_id, $handler_type, $operation);

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->hasHandlerClass($handler_type, $operation)->willReturn($knows);

    $this->entityTypeManager->getDefinition($entity_type_id)->willReturn($entity_type->reveal());

    $this->assertSame($knows, $this->sut->knowsDependency($dependency_id));
  }

  /**
   * Builds a list of booleans.
   *
   * @return array[]
   *   Values are arrays with single boolean values.
   */
  public function getKnows() {
    return [
      [TRUE],
      [FALSE ],
    ];
  }

  /**
   * @covers ::retrieveDependency
   * @covers ::extractHandlerInfo
   * @covers ::__construct
   */
  public function testRetrieveDependencyWithoutOperation() {
    $entity_type_id = 'foo';
    $handler_type = 'bar';
    $dependency_id = sprintf('%s.%s', $entity_type_id, $handler_type);

    $handler = $this->prophesize(EntityHandlerInterface::class);

    $this->entityTypeManager->getHandler($entity_type_id, $handler_type)->willReturn($handler->reveal());

    $this->assertSame($handler->reveal(), $this->sut->retrieveDependency($dependency_id));
  }

  /**
   * @covers ::retrieveDependency
   * @covers ::extractHandlerInfo
   * @covers ::__construct
   */
  public function testRetrieveDependencyWithOperation() {
    $entity_type_id = 'foo';
    $handler_type = 'form';
    $operation = 'bar';
    $dependency_id = sprintf('%s.%s.%s', $entity_type_id, $handler_type, $operation);

    $form = $this->prophesize(EntityFormInterface::class);

    $this->entityTypeManager->getFormObject($entity_type_id, $operation)->willReturn($form->reveal());

    $this->assertSame($form->reveal(), $this->sut->retrieveDependency($dependency_id));
  }

  /**
   * @covers ::retrieveDependency
   * @covers ::extractHandlerInfo
   * @covers ::__construct
   *
   * @expectedException \BartFeenstra\DependencyRetriever\Exception\UnknownDependencyException
   */
  public function testRetrieveDependencyWithoutOperationWithEntityTypeException() {
    $entity_type_id = 'foo';
    $handler_type = 'bar';
    $dependency_id = sprintf('%s.%s', $entity_type_id, $handler_type);

    $this->entityTypeManager->getHandler($entity_type_id, $handler_type)->willThrow(new InvalidPluginDefinitionException($entity_type_id));

    $this->sut->retrieveDependency($dependency_id);
  }

  /**
   * @covers ::retrieveDependency
   * @covers ::extractHandlerInfo
   * @covers ::__construct
   *
   * @expectedException \BartFeenstra\DependencyRetriever\Exception\UnknownDependencyException
   */
  public function testRetrieveDependencyWithOperationWithEntityTypeException() {
    $entity_type_id = 'foo';
    $handler_type = 'form';
    $operation = 'bar';
    $dependency_id = sprintf('%s.%s.%s', $entity_type_id, $handler_type, $operation);

    $this->entityTypeManager->getFormObject($entity_type_id, $operation)->willThrow(new InvalidPluginDefinitionException($entity_type_id));

    $this->sut->retrieveDependency($dependency_id);
  }

}
