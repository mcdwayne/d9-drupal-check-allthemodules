<?php

namespace Drupal\scriptjunkie;

use Drupal\Core\Language\LanguageInterface;

/**
 * Provides a class for CRUD operations on ScriptJunkie scripts.
 */
interface ScriptJunkieStorageInterface {

  /**
   * Saves a Script Junkie to the database.
   *
   * @param string $name
   *   The namespace of the script.
   * @param string $general
   *   Serialized array with [title, description].
   * @param string $scope
   *   The scope of the string (footer, header).
   * @param string $script
   *   The actual Script.
   * @param string $roles
   *   Serialized array with the visibility roles to skip.
   * @param string $pages
   *   Serialized array of pages where the Script will be used.
   * @param int|null $sid
   *   (optional) Unique script identifier.
   *
   * @return array|false
   *   FALSE if the script could not be saved or an associative array containing
   *   the following keys:
   *   - name (string): The namespace of the script.
   *   - sid (int): Unique Script identifier.
   *   - original: For updates, an array with name, and sid with
   *     the previous values.
   */
  public function save($name, $general = '', $scope = '', $script = '', $roles = '', $pages = '', $sid = NULL);

  /**
   * Fetches a specific Script from the database.
   *
   * The default implementation performs case-insensitive matching on the
   * 'name' string.
   *
   * @param array $conditions
   *   An array of query conditions.
   *
   * @return array|false
   *   FALSE if no script was found or an associative array containing the
   *   following keys:
   *   - name (string): The namespace of the script.
   *   - sid (int): Unique Script identifier.
   */
  public function load($conditions);

  /**
   * Deletes a Script from the database.
   *
   * The default implementation performs case-insensitive matching on the
   * 'name' string.
   *
   * @param array $conditions
   *   An array of criteria.
   */
  public function delete($conditions);

  /**
   * Checks if script already exists.
   *
   * The default implementation performs case-insensitive matching on the
   * 'name' strings.
   *
   * @param string $name
   *   Namespace to check against.
   *
   * @return bool
   *   TRUE if script already exists and FALSE otherwise.
   */
  public function scriptExists($name);

  /**
   * Retrieves all settings for a specific scriptjunkie script.
   *
   * @param array $conditions
   *   An array of criteria.
   *
   * @return array|false
   *   FALSE if no script was found or an associative array containing the
   *   following keys:
   *   - name (string): The namespace of the script.
   *   - general (array): Array with [title, description]
   *   - scope (string): The scope of the string (footer, header)
   *   - script (blob): The actual Script
   *   - roles (array): The visibility roles to skip.
   *   - pages (array): Array of pages where the Script will be used.
   *   - sid (int): Unique Script identifier.
   */
  public function getScriptJunkieSettings($conditions = array());

  /**
   * Returns a list of scriptjunkie scripts as a structured array.
   *
   * @param array $conditions
   *   An array of criteria.
   * @param string $data
   *   Bring all data or a subset.
   *
   * @return array
   *   An array structure representing all matched Scripts.
   */
  public function scriptJunkieGetScripts($conditions = array(), $data = 'all');

  /**
   * Loads scripts for admin listing.
   *
   * @param array $header
   *   Table header.
   * @param string|null $keys
   *   (optional) Search keyword that may include one or more '*' as wildcard
   *   values.
   *
   * @return array
   *   Array of items to be displayed on the current page.
   */
  public function getScriptsForAdminListing($header, $keys = NULL);

  /**
   * Namespace check.
   *
   * Check that user entered namespace is only lowercase alphanumeric and
   *   underscores.
   *
   * @param string $name
   *   Namespace to check.
   *
   * @return bool
   *   TRUE if the given namespace is valid. FALSE if not.
   */
  public function scriptJunkieIsValidNamespace($name);

}
