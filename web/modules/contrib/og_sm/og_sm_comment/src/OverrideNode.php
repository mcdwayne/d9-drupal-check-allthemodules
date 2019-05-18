<?php

/**
 * @file
 * Object containing the override state for a certain node.
 */

/**
 * Class describing the logic of the Node override state.
 */
class OgSmCommentOverrideNode {
  /**
   * The Node ID the override settings are about.
   *
   * @var int
   */
  private $nid;

  /**
   * The overridden comment setting (if any).
   *
   * @var int|null
   */
  private $comment;

  /**
   * Create the object from its settings.
   *
   * @param int $nid
   *   The Node ID.
   * @param int|null $comment
   *   The comment setting (if any).
   */
  public function __construct($nid, $comment = NULL) {
    $this->nid = (int) $nid;
    if (NULL !== $comment) {
      $this->comment = (int) $comment;
    }
  }

  /**
   * Get the Node ID.
   *
   * @return int
   *   The Node ID.
   */
  public function getNid() {
    return $this->nid;
  }

  /**
   * Is the node comment settings overridden.
   *
   * @return bool
   *   Overridden.
   */
  public function isOverridden() {
    return NULL !== $this->comment;
  }

  /**
   * Get the overridden comment setting.
   *
   * @return int|null
   *   Null when not overridden.
   */
  public function getComment() {
    return $this->comment;
  }

}
