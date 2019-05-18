<?php

namespace Drupal\Tests\graph_reference\Kernel;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\graph_reference\Entity\GraphInterface;

/**
 * Ensures that Graph Reference field definitions are created correctly
 *
 * @group graph_reference
 */
class GraphReferenceFieldDefinitionsTest extends GraphKernelTestBase {

  /**
   * @var \Drupal\graph_reference\Entity\GraphInterface
   */
  protected $graph;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->graph = $this->createGraph();
  }

  /**
   * Tests dynamic entity reference schema.
   */
  public function testGraphReferenceDefinition() {
    $field_definitions = $this->entityManager->getFieldDefinitions('user', 'user');
    $reference_field_name = $this->graph->getReferenceFieldName();

    $this->assertArrayHasKey($reference_field_name, $field_definitions, "A field with the name {$reference_field_name} exists.");

    $definition = $field_definitions[$reference_field_name];

    return $definition;
  }

  /**
   * @depends testGraphReferenceDefinition
   */
  public function testGraphReferenceFieldType() {
    $definition = $this->getGraphReferenceFieldDefinition($this->graph);
    $this->assertEquals('entity_reference', $definition->getType(), 'The reference field type is entity_reference');
  }

  /**
   * @depends testGraphReferenceDefinition
   */
  public function testGraphReferencePointsToGraph() {
    $definition = $this->getGraphReferenceFieldDefinition($this->graph);
    $this->assertEquals($this->graph->id(), $definition->getSetting('graph_id'), 'The reference field points to the correct graph.');
  }

  /**
   * @depends testGraphReferenceDefinition
   */
  public function testGraphReferenceIsComputed() {
    $definition = $this->getGraphReferenceFieldDefinition($this->graph);
    $this->assertTrue($definition->isComputed(), 'The reference field is computed.');
  }

}
