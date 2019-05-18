<?php

/**
 * @file
 * Contains \Drupal\custom_meta\CustomMetaStorageInterface.
 */

namespace Drupal\custom_meta;

use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a class for CRUD operations on custom meta tags.
 */
interface CustomMetaStorageInterface {

  /**
   * Saves a custom meta tag to the database.
   *
   * @param array $fields
   *   Array values of the custom meta tag.
   *
   * @return array|false
   *   Return saved meta tag.
   */
  public function save($fields);

  /**
   * Fetches a specific custom meta tag from the database.
   *
   * @param array $conditions
   *   An array of query conditions.
   *
   * @return array|false
   *   FALSE if no custom meta tag was found.
   */
  public function load($conditions);

  /**
   * Deletes a custom meta tag.
   *
   * @param array $conditions
   *   An array of criteria.
   */
  public function delete($conditions);

  /**
   * Checks if custom meta tag already exists.
   *
   * @param array $conditions
   *   Array values of the custom meta tag.
   *
   * @return bool
   *   TRUE if meta tag already exists and FALSE otherwise.
   */
  public function tagExists($conditions);

  /**
   * Loads custom meta tags for admin listing.
   *
   * @param array $header
   *   Table header.
   *
   * @return array
   *   Array of items to be displayed on the current page.
   */
  public function getCustomMetaTagsForAdminListing($header);

  /**
   * Loads custom meta tags for listing.
   *
   * @return array
   *   Array of items to be displayed on the current page.
   */
  public function getCustomMetaTagsListing();
}
