<?php

namespace Drupal\og_sm;

use Drupal\node\NodeInterface;
use Drupal\node\NodeTypeInterface;

/**
 * Site manager helper methods.
 */
class OgSm {

  /**
   * Helper function to dispatch a site event for site changes.
   *
   * This is used to let other modules know that an action was performed on a
   * node that is a Site type. This reduces the code module maintainers need to
   * write to detect if something changed on a Site node type.
   *
   * @param string $action
   *   Action that is triggered.
   * @param \Drupal\node\NodeInterface $node
   *   The node for who to dispatch the event.
   */
  public static function siteEventDispatch($action, NodeInterface $node) {
    static::siteManager()->eventDispatch($action, $node);
  }

  /**
   * Check if the given node is a Site type.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   *
   * @return bool
   *   Is a Site node.
   */
  public static function isSite(NodeInterface $site) {
    return static::siteManager()->isSite($site);
  }

  /**
   * Check if a given node type is a Site type.
   *
   * @param \Drupal\node\NodeTypeInterface $type
   *   The type to check.
   *
   * @return bool
   *   Is Site type.
   */
  public static function isSiteType(NodeTypeInterface $type) {
    return static::siteTypeManager()->isSiteType($type);
  }

  /**
   * Sets the site type setting on a node type.
   *
   * @param \Drupal\node\NodeTypeInterface $type
   *   The content type to add.
   * @param bool $isSiteType
   *   Whether this is a site type or not.
   */
  public static function setSiteType(NodeTypeInterface $type, $isSiteType) {
    static::siteTypeManager()->setIsSiteType($type, $isSiteType);
  }

  /**
   * Returns the site manager instance.
   *
   * @return \Drupal\og_sm\SiteManagerInterface
   *   The site manager instance.
   */
  public static function siteManager() {
    return \Drupal::service('og_sm.site_manager');
  }

  /**
   * Returns the site type manager instance.
   *
   * @return \Drupal\og_sm\SiteTypeManagerInterface
   *   The site type manager instance.
   */
  public static function siteTypeManager() {
    return \Drupal::service('og_sm.site_type_manager');
  }

}
