<?php

namespace Drupal\timed_node_page;

use Drupal\Core\Cache\CacheableDependencyInterface;

/**
 * Interface for the timed node page plugin.
 *
 * @package Drupal\timed_node_page
 */
interface TimedNodePagePluginInterface extends CacheableDependencyInterface {

  /**
   * Determines whether this node page has custom controller / response.
   *
   * @return bool
   *   Whether to use custom response.
   */
  public function usesCustomResponse();

  /**
   * Gets the custom response of this node page.
   *
   * Used only if the usesCustomResponse() is true.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The custom response.
   */
  public function getCustomResponse();

  /**
   * The bundle for which this timed page is.
   *
   * @return string
   *   The node bundle.
   */
  public function getBundle();

  /**
   * Gets the start field name.
   *
   * @return string
   *   The field name.
   */
  public function getStartFieldName();

  /**
   * Gets the end field name.
   *
   * @return string|null
   *   The field name if it exists.
   */
  public function getEndFieldName();

  /**
   * Gets the current node according to start and end.
   *
   * @param string $langcode
   *   The language in which we want to return the node.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The current node, if any.
   */
  public function getCurrentNode($langcode = '');

  /**
   * Gets the next node according to current.
   *
   * @param string $langcode
   *   The language in which we want to return the node.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The next node, if any.
   */
  public function getNextNode($langcode = '');

}
