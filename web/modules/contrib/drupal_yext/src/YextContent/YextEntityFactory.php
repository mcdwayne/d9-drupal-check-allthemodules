<?php

namespace Drupal\drupal_yext\YextContent;

use Drupal\drupal_yext\traits\CommonUtilities;
use Drupal\drupal_yext\traits\Singleton;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Entity\Node;

/**
 * An entity factory. Returns an object to manipulate Drupal entities.
 */
class YextEntityFactory {

  use CommonUtilities;
  use Singleton;

  /**
   * Get a NodeMigrateDestinationInterface based on an entity.
   *
   * If the entity does not have raw Yext data in it, ignore it.
   *
   * @param EntityInterface $entity
   *   A Drupal entity.
   *
   * @return NodeMigrateDestinationInterface
   *   A destination for migration.
   *
   * @throws \Throwable
   */
  public function destinationIfLinkedToYext(EntityInterface $entity) : NodeMigrateDestinationInterface {
    if ($entity->getEntityType()->id() != 'node') {
      return new YextIgnoreNode();
    }
    if ($entity->getType() != $this->yextNodeType()) {
      return new YextIgnoreNode();
    }
    $candidate = $this->entity($entity);
    $raw = $candidate->fieldValue($this->fieldmap()->raw());
    if (!$raw) {
      return new YextIgnoreNode();
    }
    return $candidate;
  }

  /**
   * Given a Drupal entity, return a Yext Entity.
   *
   * @param Entity $entity
   *   A Drupal entity.
   *
   * @return YextEntity
   *   A Yext-specific wrapper for a Drupal entity.
   *
   * @throws Exception
   */
  public function entity(Entity $entity) : YextEntity {
    if (is_a($entity, Node::class)) {
      $class = YextTargetNode::class;
    }
    else {
      $class = YextEntity::class;
    }
    $return = new $class();
    $return->setEntity($entity);
    return $return;
  }

  /**
   * Generates a new entity, and saves it.
   *
   * @param string $bundle
   *   A bundle such as "node".
   * @param string $type
   *   A type such as "article".
   *
   * @return YextEntity
   *   The generated entity.
   *
   * @throws Exception
   */
  public function generate(string $bundle, string $type) {
    if ($bundle == 'node') {
      $class = YextTargetNode::class;
    }
    else {
      $class = YextEntity::class;
    }
    $return = new $class();
    $return->generate();
    return $return;
  }

  /**
   * Create a node with a unique value for a field, or get existing one.
   *
   * @param string $node_type
   *   A node type such as 'doctor' or 'article'.
   * @param string $field_name
   *   A field name such as 'field_external_system_id'.
   * @param string $field_value
   *   Field value for $field_name.
   *
   * @return YextTargetNode
   *   Object of type YextTargetNode which has already been saved, and which
   *   has an id.
   *
   * @throws Exception
   */
  public function getOrCreateUniqueNode(string $node_type, string $field_name, $field_value) : YextTargetNode {
    $candidates = $this->preloadUniqueNodes($node_type, $field_name, [$field_value]);
    if (isset($candidates[$field_value])) {
      return $candidates[$field_value];
    }
    $return = $this->generate('node', $node_type);
    $return->setFieldValue($field_name, $field_value);
    $return->save();
    return $return;
  }

  /**
   * Assuming a unique field_name, load nodes for those field names.
   *
   * An exception is thrown if two or more nodes share the same value for
   * field_name.
   *
   * @param string $node_type
   *   A node type such as 'doctor' or 'article'.
   * @param string $field_name
   *   A field name such as 'field_external_system_id'.
   * @param array $field_values
   *   An array of possible field values for $field_name.
   *
   * @return array
   *   An associativ array keyed by field value with objects of type
   *   YextTargetNode.
   *
   * @throws Exception
   */
  public function preloadUniqueNodes(string $node_type, string $field_name, array $field_values) : array {
    if (empty($field_values)) {
      return [];
    }

    $query = \Drupal::entityQuery('node');
    $query->condition('type', $node_type);
    $query->condition($field_name, $field_values, 'IN');

    $nids = $query->execute();
    $nodes = node_load_multiple($nids);
    $return = [];
    foreach ($nodes as $node) {
      $entity = $this->entity($node);
      $unique = $entity->fieldValue($field_name);
      if (isset($return[$unique])) {
        throw new \Exception('More than one node seems to have the same value (' . $unique . ') which should be unique for ' . $field_name . ', ' . $entity->id() . ' and ' . $return[$unique]->id());
      }
      $return[$unique] = $entity;
    }
    return $return;
  }

}
