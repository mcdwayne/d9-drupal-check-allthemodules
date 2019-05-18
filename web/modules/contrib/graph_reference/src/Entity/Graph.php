<?php

namespace Drupal\graph_reference\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityWithPluginCollectionInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * Defines the Graph entity.
 *
 * @ConfigEntityType(
 *   id = "graph",
 *   label = @Translation("Graph"),
 *   handlers = {
 *     "list_builder" = "Drupal\graph_reference\GraphListBuilder",
 *     "form" = {
 *       "add" = "Drupal\graph_reference\Form\GraphForm",
 *       "edit" = "Drupal\graph_reference\Form\GraphForm",
 *       "delete" = "Drupal\graph_reference\Form\GraphDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\graph_reference\GraphHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "graph",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/graph/{graph}",
 *     "add-form" = "/admin/structure/graph/add",
 *     "edit-form" = "/admin/structure/graph/{graph}/edit",
 *     "delete-form" = "/admin/structure/graph/{graph}/delete",
 *     "collection" = "/admin/structure/graph"
 *   }
 * )
 */
class Graph extends ConfigEntityBase implements GraphInterface {

  /**
   * The Graph ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Graph label.
   *
   * @var string
   */
  protected $label;

  /**
   * The vertex set configuration
   *
   * @var array
   */
  protected $vertex_set;

  /**
   * {@inheritdoc}
   */
  public function getReferenceFieldName() {
    return $this->id() . '_reference';
  }


  /**
   * {@inheritdoc}
   */
  public function getEdgesOf(FieldableEntityInterface $vertex) {
    if ($vertex->isNew()) {
      return [];
    }

    $edges = [];

    foreach ($this->doGetEdgeEntitiesOf($vertex) as $graph_edge) {
      $edges[] = $graph_edge->getOtherVertex($vertex);
    }

    return $edges;
  }

  /**
   * {@inheritdoc}
   */
  public function setEdgesOf(FieldableEntityInterface $vertex, array $edges) {
    $storage = $this->entityTypeManager()->getStorage('graph_edge');

    /** @var \Drupal\Core\Entity\EntityInterface[] $edges_to_create */
    $edge_entities_to_delete = [];

    /** @var \Drupal\Core\Entity\FieldableEntityInterface[] $edges_to_create */
    $edges_to_create = [];

    // Existing edges will be removed from this array later on.
    foreach ($edges as $edge) {
      $edges_to_create[$edge->uuid()] = $edge;
    }

    $edge_keys = array_keys($edges_to_create);

    $edge_entities = $this->doGetEdgeEntitiesOf($vertex);

    foreach ($edge_entities as $current_edge_entity) {
      $current_edge = $current_edge_entity->getVertexA()->id() == $vertex->id() ? $current_edge_entity->getVertexB() : $current_edge_entity->getVertexA();
      if (in_array($current_edge->uuid(), $edge_keys)) {
        unset($edges_to_create[$current_edge->uuid()]);
      }
      else {
        $edge_entities_to_delete[] = $current_edge_entity;
      }
    }

    $edge_entities_to_save = [];
    foreach ($edges_to_create as $edge) {
      $edge_entities_to_save[] = $storage->create([
        'graph' => $this->id(),
        'vertex_a' => $vertex,
        'vertex_b' => $edge
      ]);

      // Set the value of the vertex on the entity so it is accessible when
      // requested (before reloading).
      $edge->get($this->getReferenceFieldName())->appendItem($vertex);
    }

    // Save the required edges.
    foreach ($edge_entities_to_save as $edge_entity) {
      $edge_entity->save();
    }

    // Delete the redundant edges.
    foreach ($edge_entities_to_delete as $edge_entity) {
      $edge_entity->delete();
    }

  }

  /**
   * @param \Drupal\Core\Entity\FieldableEntityInterface $vertex
   * @return \Drupal\graph_reference\Entity\GraphEdgeInterface[]
   */
  protected function doGetEdgeEntitiesOf(FieldableEntityInterface $vertex) {
    if ($vertex->isNew()) {
      return [];
    }

    $storage = $this->entityTypeManager()->getStorage('graph_edge');
    $edge_query = $storage->getQuery();
    $edge_query
      ->addTag('graph_edges')
      ->addMetaData('graph_id', $this->id())
      ->addMetaData('source_vertex_id', $vertex->id())
      ->addMetaData('source_vertex_type', $vertex->getEntityTypeId());
    $edge_query->condition('graph', $this->id());
    $a_or_b = $edge_query->orConditionGroup();
    foreach (['vertex_a', 'vertex_b'] as $vertex_id) {
      $vertex_condition = $edge_query->andConditionGroup();

      foreach (['target_type' => $vertex->getEntityTypeId(), 'target_id' => $vertex->id()] as $property => $value) {
        $vertex_condition->condition("{$vertex_id}__{$property}", $value);
      }

      $a_or_b->condition($vertex_condition);
    }
    $edge_query->condition($a_or_b);

    return $storage->loadMultiple($edge_query->execute());
  }
}
