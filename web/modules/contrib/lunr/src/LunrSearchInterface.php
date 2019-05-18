<?php

namespace Drupal\lunr;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a Lunr search entity.
 */
interface LunrSearchInterface extends ConfigEntityInterface {

  /**
   * Returns the path.
   *
   * @return string
   *   The path.
   */
  public function getPath();

  /**
   * Returns the view ID.
   *
   * @return string
   *   The view ID.
   */
  public function getViewId();

  /**
   * Returns the view display ID.
   *
   * @return string
   *   The view display ID.
   */
  public function getViewDisplayId();

  /**
   * Returns the index fields.
   *
   * @return array
   *   An associative array mapping field names to attributes.
   */
  public function getIndexFields();

  /**
   * Returns the display field.
   *
   * @return string
   *   The display field.
   */
  public function getDisplayField();

  /**
   * Returns the number of search results per page.
   *
   * @return int
   *   The number of search results per page.
   */
  public function getResultsPerPage();

  /**
   * Gets the view used by this search, using the correct display.
   *
   * @return \Drupal\views\ViewExecutable
   *   The view.
   */
  public function getView();

  /**
   * Gets the path to the index file.
   *
   * @return string
   *   The path to the index file.
   */
  public function getIndexPath();

  /**
   * Gets the base path of all indexes.
   *
   * @return string
   *   The base path to all index files.
   */
  public function getBaseIndexPath();

  /**
   * Gets the path pattern for document files.
   *
   * This can be used by replacing "PAGE" with the page number.
   *
   * @return string
   *   The path pattern for document files.
   */
  public function getDocumentPathPattern();

  /**
   * Gets the last index time.
   *
   * @return int
   *   The last index time.
   */
  public function getLastIndexTime();

  /**
   * Sets the path.
   *
   * @param string $path
   *   The path.
   */
  public function setPath($path);

  /**
   * Sets the view ID.
   *
   * @param string $view_id
   *   The view ID.
   */
  public function setViewId($view_id);

  /**
   * Sets the view display ID.
   *
   * @param string $view_display_id
   *   The view display ID.
   */
  public function setViewDisplayId($view_display_id);

  /**
   * Sets the index fields.
   *
   * @param array $fields
   *   An associative array mapping field names to attributes.
   */
  public function setIndexFields(array $fields);

  /**
   * Sets the display field.
   *
   * @param string $field
   *   The display field.
   */
  public function setDisplayField($field);

  /**
   * Sets the number of search results per page.
   *
   * @param int $number
   *   The number of search results per page.
   */
  public function setResultsPerPage($number);

  /**
   * Sets the last index time.
   *
   * The Lunr search does not need re-saved after setting this.
   *
   * @param int $timestamp
   *   The last index time.
   */
  public function setLastIndexTime($timestamp);

}
