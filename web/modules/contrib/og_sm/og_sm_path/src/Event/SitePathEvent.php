<?php

namespace Drupal\og_sm_path\Event;

use Drupal\node\NodeInterface;
use Drupal\og_sm\Event\SiteEvent;

/**
 * Defines the site event.
 *
 * @see \Drupal\og_sm\Event\SiteEvents
 */
class SitePathEvent extends SiteEvent {

  /**
   * The original path.
   *
   * @var string
   */
  protected $originalPath;

  /**
   * The new site path.
   *
   * @var string
   */
  protected $path;

  /**
   * Constructs a site path event object.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   * @param string $original_path
   *   The original site path.
   * @param string $path
   *   The new site path.
   */
  public function __construct(NodeInterface $site, $original_path, $path) {
    parent::__construct($site);
    $this->originalPath = $original_path;
    $this->path = $path;
  }

  /**
   * Gets the original site path.
   *
   * @return string
   *   The original site path.
   */
  public function getOriginalPath() {
    return $this->originalPath;
  }

  /**
   * Gets the new site path.
   *
   * @return string
   *   The new site path.
   */
  public function getPath() {
    return $this->path;
  }

}
