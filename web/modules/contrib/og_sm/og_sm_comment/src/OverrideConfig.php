<?php

/**
 * @file
 * Object containing the Comment Override Config.
 */

/**
 * Object describing the Comment Override Config.
 */
class OgSmCommentOverrideConfig {

  /**
   * The global comment level.
   *
   * @var int
   */
  private $globalComment;

  /**
   * The site comment level.
   *
   * @var int
   */
  private $siteComment;

  /**
   * A default value when overridden.
   *
   * @var int|null
   */
  private $defaultComment;

  /**
   * Override is active.
   *
   * @var bool
   */
  private $overridable = FALSE;

  /**
   * Construct a new object from overridable & default value.
   *
   * @param int $global
   *   The global comment value.
   * @param int $site
   *   The site comment value.
   * @param bool $overridable
   *   Are the comment settings overridable per content item?
   * @param int|null $default
   *   The default comment level when the comment settings are overridable. Set
   *   to NULL not set a default value.
   */
  public function __construct($global, $site = NULL, $overridable = FALSE, $default = NULL) {
    $this->globalComment = (int) $global;

    // Site config only relevant if comments are enabled globally.
    if (NULL !== $site && $this->getGlobalComment() === OgSmCommentLevels::OPEN) {
      $this->siteComment = (int) $site;
    }

    // Only overridable if Site has comment settings.
    if ($this->hasSiteComment()) {
      $this->overridable = (bool) $overridable;
    }
    if (NULL !== $default && '' !== $default && $this->isOverridable()) {
      // Default can't be higher then the Site config.
      $this->defaultComment = $default > $this->getSiteComment()
        ? $this->getSiteComment()
        : (int) $default;
    }
  }

  /**
   * Are comments globally enabled.
   *
   * @return bool
   *   Enabled.
   */
  public function isGlobalEnabled() {
    return $this->getGlobalComment() >= OgSmCommentLevels::OPEN;
  }

  /**
   * Get the global comment value.
   *
   * @return int
   *   The global comment value.
   */
  public function getGlobalComment() {
    return $this->globalComment;
  }

  /**
   * Are comments enabled on Site level.
   *
   * @return bool
   *   Enabled.
   */
  public function isSiteEnabled() {
    if (!$this->hasSiteComment()) {
      return $this->isGlobalEnabled();
    }

    return $this->getSiteComment() >= OgSmCommentLevels::OPEN;
  }

  /**
   * Has a site value.
   *
   * @return bool
   *   Has site comment value set.
   */
  public function hasSiteComment() {
    return NULL !== $this->siteComment;
  }

  /**
   * Get the site comment level.
   *
   * @return int
   *   The site comment level.
   */
  public function getSiteComment() {
    return $this->siteComment;
  }

  /**
   * Are the comment settings overridable?
   *
   * @return bool
   *   Overridable.
   */
  public function isOverridable() {
    return $this->overridable;
  }

  /**
   * Has a default value.
   *
   * @return bool
   *   Has default value.
   */
  public function hasDefaultComment() {
    return NULL !== $this->defaultComment;
  }

  /**
   * Get the default value.
   *
   * @return int|null
   *   The default value when overridden.
   */
  public function getDefaultComment() {
    return $this->defaultComment;
  }

}
