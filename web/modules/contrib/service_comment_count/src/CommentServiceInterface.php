<?php

namespace Drupal\service_comment_count;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines an interface for Comment service plugins.
 */
interface CommentServiceInterface extends PluginInspectionInterface {

  /**
   * Returns the label of the comment service.
   *
   * @return string
   *   The label of the comment service.
   */
  public function getLabel();

  /**
   * Checks, whether the service is valid configured to fetch results.
   *
   * @return bool
   *   True, if the service is ready to fetch results.
   */
  public function isValid();

  /**
   * Builds the identifier the Comment Service uses for the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The processed entity.
   *
   * @return string
   *   The unique identifier.
   */
  public function buildIdentifier(ContentEntityInterface $entity);

  /**
   * Fetches the comment count for a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity for which the comment count should be fetched.
   *
   * @return int
   *   Comment count for the given entity.
   */
  public function fetchCommentCount(ContentEntityInterface $entity);

  /**
   * Fetches the comment count for a given list of entities.
   *
   * @param array $entities
   *   The entities for which the comment counts should be fetched.
   *
   * @return int
   *   Comment count for the given entity.
   */
  public function fetchCommentCountMultiple(array $entities);

}
