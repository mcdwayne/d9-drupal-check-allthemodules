<?php

namespace Drupal\relatedbyterms;

/**
 * Interface RelatedByTermsServiceInterface.
 *
 * @package Drupal\relatedbyterms
 */
interface RelatedByTermsServiceInterface {

  /**
   * Get a list of related nodes.
   *
   * You can call this function directly from your module
   * to get a list of related nodes.
   *
   * @param string $nid
   *   Node id to look for related content.
   * @param string $langcode
   *   Langcode to use to recover content. Defaults to user selected.
   * @param int $limit
   *   Max number of nodes to display. Defaults to the value configured
   *   in the module settings page.
   *
   * @return array
   *   Array with a list of nid of related contents.
   */
  public function getRelatedNodes($nid, $langcode = NULL, $limit = -1);

  /**
   * Returns the default block title.
   */
  public function getDefaultTitle();

  /**
   * Sets the default block title.
   */
  public function setDefaultTitle($defaultTitle);

  /**
   * Returns the display mode of the elements.
   *
   * From the configuration values. Can be set in the module config page.
   */
  public function getDisplayMode();

  /**
   * Sets the display mode of the elements.
   */
  public function setDisplayMode($displayMode);

  /**
   * Returns the number of elements to show.
   *
   * From the configuration values. Can be set in the module config page.
   */
  public function getElementsDisplayed();

  /**
   * Sets the number of elements to show.
   */
  public function setElementsDisplayed($limit);

}
