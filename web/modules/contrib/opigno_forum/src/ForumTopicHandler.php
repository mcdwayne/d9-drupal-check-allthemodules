<?php

namespace Drupal\opigno_forum;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the relationship between forum topics and groups.
 */
class ForumTopicHandler implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * ForumTopicHandler constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Returns a forum topic handler.
   *
   * @return static
   *   An instance of the forum topic handler.
   */
  public static function get() {
    /** @var static $handler */
    $handler = \Drupal::classResolver()->getInstanceFromDefinition(static::class);
    return $handler;
  }

  /**
   * Performs node creation tasks.
   *
   * @param \Drupal\node\NodeInterface $node
   *   A newly created node.
   */
  public function onNodeInsert(NodeInterface $node) {
    $node_type_id = $node->bundle();
    if ($this->isForumTopicType($node_type_id) && $this->isLearningPathContent($node_type_id) && ($gid = $this->getForumTopicGroupId($node))) {
      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = $this->entityTypeManager->getStorage('group')->load($gid);
      // The target entity will be saved again while being added to the group,
      // which would trigger recursive save and integrity constraint violation
      // issues in the forum integration logic.
      // @todo Perform the addition here once Group deals with additions in a
      //   saner way. See https://www.drupal.org/node/2892512#comment-12821461.
      drupal_register_shutdown_function(function () use ($group, $node) {
        $group->addContent($node, 'group_node:' . $node->bundle());
      });
    }
  }

  /**
   * Checks whether a node type is configured as learning path content.
   *
   * @param string $node_type_id
   *   The node type ID.
   *
   * @return bool
   *   TRUE if the node is configured as learning path content, FALSE otherwise.
   */
  public function isLearningPathContent($node_type_id) {
    try {
      /** @var \Drupal\group\Entity\GroupTypeInterface $group_type */
      $group_type = $this->entityTypeManager->getStorage('group_type')->load('learning_path');
      $group_type->getContentPlugin('group_node:' . $node_type_id);
      return TRUE;
    }
    catch (PluginNotFoundException $e) {
      return FALSE;
    }
  }

  /**
   * Checks whether nodes of the specified type are forum topics.
   *
   * @param string $node_type_id
   *   A node type ID.
   *
   * @return bool
   *   TRUE if nodes of the specified type are forum topics, FALSE otherwise.
   */
  public function isForumTopicType($node_type_id) {
    $definitions = $this->entityFieldManager->getFieldDefinitions('node', $node_type_id);
    return isset($definitions['taxonomy_forums']);
  }

  /**
   * Returns the group associated with the specified topic.
   *
   * @param \Drupal\node\NodeInterface $topic
   *   A forum topic node object.
   *
   * @return string|null
   *   A group ID.
   */
  public function getForumTopicGroupId(NodeInterface $topic) {
    $tid = $topic->get('taxonomy_forums')->target_id;

    $ids = $this->entityTypeManager
      ->getStorage('group')
      ->getQuery()
      ->condition('field_learning_path_enable_forum', 1)
      ->condition('field_learning_path_forum', $tid)
      ->accessCheck(FALSE)
      ->execute();

    return $ids ? reset($ids) : NULL;
  }

  /**
   * Returns a list of forum topic types used as learning path content.
   *
   * @return string[]
   *   An array of node type IDs.
   */
  public function getForumTopicTypeIds() {
    $topic_type_ids = [];
    $result = $this->entityTypeManager
      ->getStorage('group')
      ->getAggregateQuery()
      ->groupBy('field_learning_path_forum')
      ->aggregate('field_learning_path_enable_forum', 'COUNT')
      ->condition('field_learning_path_enable_forum', 1)
      ->accessCheck(FALSE)
      ->execute();

    $tids = array_map(function ($record) { return $record['field_learning_path_forum_target_id']; }, $result);
    if ($tids) {
      $result = $this->entityTypeManager
        ->getStorage('node')
        ->getAggregateQuery()
        ->groupBy('type')
        ->aggregate('nid', 'COUNT')
        ->condition('taxonomy_forums', $tids, 'IN')
        ->accessCheck(FALSE)
        ->execute();
      $topic_type_ids = array_map(function ($record) { return $record['type']; }, $result);
    }

    return $topic_type_ids;
  }

}
