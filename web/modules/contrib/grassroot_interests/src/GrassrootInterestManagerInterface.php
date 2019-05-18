<?php

/**
 * @file
 * Contains \Drupal\grassroot_interests\GrassrootInterestManagerInterface.
 */

namespace Drupal\grassroot_interests;

/**
 * Provides an interface defining a GrassrootInterest manager.
 */
interface GrassrootInterestManagerInterface {

  /**
   * Returns if this Keywords present.
   *
   * @param string $url_id
   *   The url_id to check.
   *
   * @return bool
   *   TRUE if keywords associated with the url_id are present, FALSE otherwise.
   */
  public function checkKeywords($url_id);

  /**
   * Gets keywords associated with url_id.
   *
   * @param string $url_id
   *   The ID for keywords to retrieve.
   *
   * @return array
   *   Associative array of keywords details associated with url_id, containing:
   *   - url_id: URL Unique.
   *   - title: Title of keyword.
   *   - root_url: Grassroots URL.
   *   - keywords: keywords.
   */
  public function getKeywordsByID($url_id);

  /**
   * Gets all grassroot keywords list.
   *
   * @return \Drupal\Core\Database\StatementInterface
   *   The result of the database query.
   */
  public function getAll();

  /**
   * Saves an Keywords.
   *
   * @param array $keywords
   *   An associative array containing:
   *   - url_id: URL Unique.
   *   - title: Title of keyword.
   *   - root_url: Grassroots URL.
   *   - keywords: array of keywords to save.
   */
  public function saveKeywords($grassroot_data);

  /**
   * Deletes keywords entries.
   *
   * @param string $url_id
   *   The url_id to delete all keywords associated with.
   */
  public function deleteKeywords($url_id);

}
