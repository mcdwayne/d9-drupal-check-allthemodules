<?php

namespace Drupal\og_sm_path;

use Drupal\node\NodeInterface;

/**
 * Interface for site path manager classes.
 */
interface SitePathManagerInterface {

  /**
   * Gets a site path based on the passed site node.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The site node.
   *
   * @return string
   *   The site path.
   */
  public function getPathFromSite(NodeInterface $site);

  /**
   * Returns an alias of Drupal system URL.
   *
   * The default implementation performs case-insensitive matching on the
   * 'source' and 'alias' strings.
   *
   * @param string $path
   *   The path to investigate for corresponding path aliases.
   *
   * @return string|false
   *   A path alias, or FALSE if no path was found.
   */
  public function lookupPathAlias($path);

  /**
   * Gets a site node based on the passed path.
   *
   * @param string $path
   *   The site path.
   *
   * @return \Drupal\node\NodeInterface|false
   *   Tge site node, FALSE if the passed path is not a site path.
   */
  public function getSiteFromPath($path);

  /**
   * Delete all aliases for the given Site.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The Site node to delete the aliases for.
   */
  public function deleteSiteAliases(NodeInterface $site);

  /**
   * Set a new path for a Site.
   *
   * @param \Drupal\node\NodeInterface $site
   *   The Site to set the path for.
   * @param string $path
   *   The path to set.
   * @param bool $trigger_event
   *   By default the SitePathEvents::CHANGE event will be called when the path
   *   is changed for a Site. This is not always wanted (eg. when a new Site is
   *   created). Set the parameter to FALSE to prevent the event from being
   *   triggered.
   */
  public function setPath(NodeInterface $site, $path, $trigger_event = TRUE);

}
