<?php

namespace Drupal\graph_reference\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Graph edge entity.
 *
 * @ingroup graph_reference
 *
 * @ContentEntityType(
 *   id = "graph_edge",
 *   label = @Translation("Graph edge"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *     },
 *   },
 *   base_table = "graph_edge",
 *   admin_permission = "administer graph edge entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *   }
 * )
 */
class GraphEdge extends ContentEntityBase implements GraphEdgeInterface {

  use EntityChangedTrait;

  /**
   * @var \Drupal\Core\Entity\FieldableEntityInterface[]
   */
  protected static $edge_cache = [];

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGraph() {
    return $this->get('graph')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getVertexA() {
    $target_type = $this->get('vertex_a')->target_type;
    $target_id = $this->get('vertex_a')->target_id;
    return $this->doGetVertex($target_type, $target_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getVertexB() {
    $target_type = $this->get('vertex_b')->target_type;
    $target_id = $this->get('vertex_b')->target_id;
    return $this->doGetVertex($target_type, $target_id);
  }

  /**
   * @param string $target_type
   * @param string|int $target_id
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   */
  protected function doGetVertex($target_type, $target_id) {
    $key = "{$target_type}:{$target_id}";
    if (empty(static::$edge_cache[$key])) {
      static::$edge_cache[$key] = NULL;
      $storage = $this->entityTypeManager()->getStorage($target_type);
      static::$edge_cache[$key] = $storage->load($target_id);
    }

    return static::$edge_cache[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function getOtherVertex(FieldableEntityInterface $vertex) {
    $target_type = $vertex->getEntityTypeId();
    $target_id = $vertex->id();
    $key = "{$target_type}:{$target_id}";
    static::$edge_cache[$key] = $vertex;

    if ($target_type == $this->get('vertex_a')->target_type
      && $target_id == $this->get('vertex_a')->target_id
    ) {
      return $this->getVertexB();
    }
    else {
      return $this->getVertexA();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts([
      $this->getVertexA()->getCacheContexts(),
      $this->getVertexB()->getCacheContexts()
    ],parent::getCacheContexts());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    $tags = parent::getCacheTagsToInvalidate();
    $tags = Cache::mergeTags($tags, $this->getVertexA()->getCacheTagsToInvalidate());
    $tags = Cache::mergeTags($tags, $this->getVertexB()->getCacheTagsToInvalidate());

    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['graph'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Graph'))
      ->setDescription(t('The graph that this edge is part of.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'graph')
      ->setSetting('handler', 'default');

    foreach (['vertex_a' => t('Vertex A'), 'vertex_b' => t('Vertex B')] as $vertex_id => $vertex_label) {
      $fields[$vertex_id] = BaseFieldDefinition::create('dynamic_entity_reference')
        ->setLabel($vertex_label)
        ->setDescription(t('A reference to one of the vertices of this edge.'))
        ->setRequired(TRUE)
        ->setSetting('handler', 'default');
    }

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Graph edge entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
