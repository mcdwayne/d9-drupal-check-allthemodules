<?php

namespace Drupal\medium_posts;

use Drupal\node\NodeInterface;

/**
 * Defines interface for Medium Posts Manager.
 *
 * @package Drupal\medium_posts
 */
interface MediumPostsManagerInterface {

  /**
   * Check to see if the node content type is selected for medium posts.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node object.
   *
   * @return bool
   *   TRUE if the node type is selected for medium posts.
   */
  public function isMediumNodeType(NodeInterface $node);

  /**
   * Check to see if the node has already been pushed to medium.com.
   *
   * @param string $uuid
   *   UUID.
   *
   * @return bool
   *   TRUE if it has been pushed already.
   */
  public function isPublished($uuid);

  /**
   * Push the node content to medium.com.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node object.
   */
  public function publish(NodeInterface $node);

  /**
   * Get the medium post url in medium.com.
   *
   * @param string $uuid
   *   Node uuid.
   *
   * @return string|bool
   *   Url string or FALSE if no result found.
   */
  public function getMediumPostUrl($uuid);

}
