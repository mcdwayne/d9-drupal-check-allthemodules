<?php

/**
 * @file
 * Access object to determen if user has access to post a comment.
 */

/**
 * The Access class combines Site config and Node override to get the access.
 */
class OgSmCommentAccess {
  /**
   * The config for the node type for the site.
   *
   * @var OgSmCommentOverrideConfig
   */
  private $config;

  /**
   * The node override settings for the node.
   *
   * @var OgSmCommentOverrideNode
   */
  private $override;

  /**
   * Construct the access object from Config, Override and permission.
   *
   * @param OgSmCommentOverrideConfig $config
   *   The site config.
   * @param OgSmCommentOverrideNode $override
   *   The override settings.
   */
  public function __construct(OgSmCommentOverrideConfig $config, OgSmCommentOverrideNode $override) {
    $this->config = $config;
    $this->override = $override;
  }

  /**
   * Has the user access to create a comment?
   *
   * @param bool $access
   *   The user has the create comment permission.
   * @param bool $anonymous
   *   The user is anonymous.
   *
   * @return bool
   *   Access.
   */
  public function canCreate($access, $anonymous) {
    if (!$access) {
      return FALSE;
    }

    if ($anonymous
      && $this->getCommentLevel() !== OgSmCommentLevels::OPEN_ANONYMOUS
    ) {
      return FALSE;
    }

    return $this->getCommentLevel() >= OgSmCommentLevels::OPEN;
  }

  /**
   * Can view list of comments.
   *
   * @return bool
   *   Access.
   */
  public function canViewList() {
    return $this->getCommentLevel() >= OgSmCommentLevels::CLOSED;
  }

  /**
   * Are the comments hidden?
   *
   * @return bool
   *   Closed.
   */
  public function commentsAreHidden() {
    return OgSmCommentLevels::HIDDEN === $this->getCommentLevel();
  }

  /**
   * Are the comments closed?
   *
   * @return bool
   *   Closed.
   */
  public function commentsAreClosed() {
    return OgSmCommentLevels::CLOSED === $this->getCommentLevel();
  }

  /**
   * Are the comments open?
   *
   * @return bool
   *   Open.
   */
  public function commentsAreOpen() {
    return OgSmCommentLevels::OPEN === $this->getCommentLevel();
  }

  /**
   * Are the comments open for anonymous?
   *
   * @return bool
   *   Closed.
   */
  public function commentsAreOpenAnonymous() {
    return OgSmCommentLevels::OPEN_ANONYMOUS === $this->getCommentLevel();
  }

  /**
   * Get the comment level.
   *
   * @return int
   *   The comment level.
   *
   * @see OgSmCommentLevels
   */
  protected function getCommentLevel() {
    if ($this->override->isOverridden()) {
      return $this->override->getComment();
    }

    if ($this->config->isOverridable() && $this->config->hasDefaultComment()) {
      return $this->config->getDefaultComment();
    }

    if ($this->config->hasSiteComment()) {
      return $this->config->getSiteComment();
    }

    return $this->config->getGlobalComment();
  }

}
