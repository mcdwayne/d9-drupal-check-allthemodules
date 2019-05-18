<?php

namespace Drupal\Tests\graph_reference\Kernel;

/**
 * Ensures that Graph Reference fields are working correctly
 *
 * @group graph_reference
 */
class GraphReferenceFieldTest extends GraphKernelTestBase {

  /**
   * @var \Drupal\graph_reference\Entity\GraphInterface
   */
  protected $graph;

  /**
   * @var \Drupal\user\UserInterface[]
   */
  protected $vertices;

  /**
   * @var string
   */
  protected $referenceFieldName;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('graph_edge');
    $this->graph = $this->createGraph();
    $this->referenceFieldName = $this->graph->getReferenceFieldName();
    $this->vertices = array_map(function() {
      return $this->createUser();
    }, range(0,9));
  }

  /**
   * Check that the reference field works from the source to the target.
   */
  public function testReferenceField() {
    $source = $this->vertices[0];
    $expected_edges = [$this->vertices[1], $this->vertices[2], $this->vertices[3], $this->vertices[4]];

    $source->set($this->graph->getReferenceFieldName(), $expected_edges)->save();

    $source = $this->reloadEntity($source);
    $this->reloadEntities($expected_edges);

    $actual_edges = $source->get($this->graph->getReferenceFieldName());

    $this->assertSameSize($expected_edges, $actual_edges, 'The number of edges is as expected');

    foreach ($actual_edges as $delta => $actual_edge) {
      $this->assertEquals($expected_edges[$delta]->toArray(), $actual_edge->entity->toArray(), "Edge #{$delta} is as expected.");
    }
  }

  /**
   * Check that the reference field works from the source to the target.
   */
  public function testReferenceFieldNoSave() {
    $source = $this->vertices[0];
    $expected_edges = [$this->vertices[1], $this->vertices[2], $this->vertices[3], $this->vertices[4]];

    $source->set($this->graph->getReferenceFieldName(), $expected_edges);

    $actual_edges = $source->get($this->graph->getReferenceFieldName());

    $this->assertSameSize($expected_edges, $actual_edges, 'The number of edges is as expected');

    foreach ($actual_edges as $delta => $actual_edge) {
      $this->assertEquals($expected_edges[$delta]->toArray(), $actual_edge->entity->toArray(), "Edge #{$delta} is as expected.");
    }
  }

  /**
   * Check that the reference field works from the target to the source.
   */
  public function testReferenceFieldReverse() {
    $targets = [$this->vertices[1], $this->vertices[2], $this->vertices[3], $this->vertices[4]];

    $this->vertices[0]->set($this->graph->getReferenceFieldName(), $targets)->save();

    $this->reloadEntities($targets);

    foreach ($targets as $target) {
      $actual_field_list = $target->get($this->graph->getReferenceFieldName());
      $this->assertEquals(1, count($actual_field_list), 'Target vertex has only 1 edge.');
      $this->assertEquals($this->vertices[0]->toArray(), $actual_field_list[0]->entity->toArray(), 'Target vertex edge equals the origin vertex.');
    }
  }

  /**
   * Check that the reference field works from the target to the source.
   */
  public function testReferenceFieldReverseNoSave() {
    $targets = [$this->vertices[1], $this->vertices[2], $this->vertices[3], $this->vertices[4]];

    $this->vertices[0]->set($this->graph->getReferenceFieldName(), $targets);

    foreach ($targets as $target) {
      $actual_field_list = $target->get($this->graph->getReferenceFieldName());
      $this->assertEquals(1, count($actual_field_list), 'Target vertex has only 1 edge.');
      $this->assertEquals($this->vertices[0]->toArray(), $actual_field_list[0]->entity->toArray(), 'Target vertex edge equals the origin vertex.');
    }
  }

  /**
   * Test the removal of references.
   */
  public function testEdgeDeletion() {
    $source = $this->vertices[0];
    $target = $this->vertices[1];

    $source->set($this->referenceFieldName, $target)->save();

    $source = $this->reloadEntity($source);
    $target = $this->reloadEntity($target);

    $this->assertEquals($target->toArray(), $source->get($this->referenceFieldName)->entity->toArray());
    $this->assertEquals($source->toArray(), $target->get($this->referenceFieldName)->entity->toArray());

    $source->set($this->referenceFieldName, [])->save();

    $source = $this->reloadEntity($source);
    $target = $this->reloadEntity($target);

    $this->assertEmpty($source->get($this->referenceFieldName));
    $this->assertEmpty($target->get($this->referenceFieldName));
  }

  /**
   * Test the removal of references.
   */
  public function testEdgeDeletionNoSave() {
    $source = $this->vertices[0];
    $target = $this->vertices[1];

    $source->set($this->referenceFieldName, $target);

    $this->assertEquals($target->toArray(), $source->get($this->referenceFieldName)->entity->toArray());
    $this->assertEquals($source->toArray(), $target->get($this->referenceFieldName)->entity->toArray());

    $source->set($this->referenceFieldName, []);

    $this->assertEmpty($source->get($this->referenceFieldName));
    $this->assertEmpty($target->get($this->referenceFieldName));
  }

  public function testCircleReference() {
    $sources = $this->vertices;
    $targets = $sources;
    array_push($targets, $sources[0]);
    array_shift($targets);
    for ($i = 0; $i < count($sources); $i++) {
      $sources[$i]->get($this->referenceFieldName)->appendItem($targets[$i]);
      $sources[$i]->save();
    }

    $this->reloadEntities($sources);
    $targets = $sources;
    array_push($targets, $sources[0]);
    array_shift($targets);

    for ($i = 0; $i < count($sources); $i++) {
      $reference_field = $sources[$i]->get($this->referenceFieldName);
      $this->assertEquals(2, $reference_field->count(), "Source #{$i} has 2 edges");
      $actual_edges = [
        $reference_field[0]->target_id,
        $reference_field[1]->target_id
      ];

      $expected_edges = [$targets[$i]->id()];

      if ($i == 0) {
        $expected_edges[] = $sources[count($sources) - 1]->id();
      }
      else {
        $expected_edges[] = $sources[$i-1]->id();
      }

      $this->assertTrue(in_array($expected_edges[0], $actual_edges), 'The current vertex points to the next vertex');
      $this->assertTrue(in_array($expected_edges[1], $actual_edges), 'The current vertex is pointed by the previous vertex.');
    }
  }

  public function testCircleReferenceNoSave() {
    $sources = $this->vertices;
    $targets = $sources;
    array_push($targets, $sources[0]);
    array_shift($targets);
    for ($i = 0; $i < count($sources); $i++) {
      $sources[$i]->get($this->referenceFieldName)->appendItem($targets[$i]);
    }

    for ($i = 0; $i < count($sources); $i++) {
      $reference_field = $sources[$i]->get($this->referenceFieldName);
      $this->assertEquals(2, $reference_field->count(), "Source #{$i} has 2 edges");
      $actual_edges = [
        $reference_field[0]->target_id,
        $reference_field[1]->target_id
      ];

      $expected_edges = [$targets[$i]->id()];

      if ($i == 0) {
        $expected_edges[] = $sources[count($sources) - 1]->id();
      }
      else {
        $expected_edges[] = $sources[$i-1]->id();
      }

      $this->assertTrue(in_array($expected_edges[0], $actual_edges), 'The current vertex points to the next vertex');
      $this->assertTrue(in_array($expected_edges[1], $actual_edges), 'The current vertex is pointed by the previous vertex.');
    }
  }

  /**
   * Reload a set of entities and returns it.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] &$entities
   * @return array
   */
  protected function reloadEntities(array &$entities) {
    $entities = array_map([$this, 'reloadEntity'], $entities);
    return $entities;
  }
}
