<?php

namespace Drupal\media_elvis;

/**
 * Interface MediaElvisServicesInterface.
 */
interface MediaElvisServicesInterface {
  /**
   * Authenticates the user.
   *
   * @return string
   *   The session id. If login failed returns empty string.
   *
   * @see https://helpcenter.woodwing.com/hc/en-us/articles/206334215-Elvis-4-REST-API-login
   */
  public function login();

  /**
   * Search assets in Elvis.
   *
   * @see https://helpcenter.woodwing.com/hc/en-us/articles/205655265
   * @see https://helpcenter.woodwing.com/hc/en-us/articles/205635049-Elvis-4-REST-API-search-
   *
   * @param string $query
   *   The search string: ie flowers.
   * @param int $offset
   *   (optional) First hit to be returned. Defaults to 0.
   * @param int $per_page
   *   (optional) Number of hits to return. Defaults to 50.
   * @param string $sort
   *   (optional) The sort order of returned hits. Examples are:
   *     - sort=name
   *     - sort=fileSize-asc
   *     - sort=status,assetModified-desc
   *   Defaults to assetCreated-desc.
   * @param array $additional
   *   (optional) Array of additional search parameters to append to the query.
   *   Look into the documentation links provided by this docblock. Defaults to
   *   empty array.
   *
   * @return \stdClass[]
   *   Array of search result hits.
   */
  public function search($query, $offset = 0, $per_page = 50, $sort = 'assetCreated-desc', array $additional = []);

  /**
   * Browse folders.
   *
   * @see https://helpcenter.woodwing.com/hc/en-us/articles/205634929-Elvis-4-REST-API-browse
   *
   * @param string $path
   *   (optional) The folder path. Defaults to empty string.
   * @param string $from_root
   *   (optional) A root folder from where to start the tree.
   *
   * @return \stdClass[]
   *   Array of folder objects.
   */
  public function browse($path = '', $from_root = '');

  /**
   * This call updates an existing asset in Elvis with a new file/metadata.
   *
   * @see https://helpcenter.woodwing.com/hc/en-us/articles/205635069-Elvis-4-REST-API-update-
   */
  public function update();

  /**
   * Sets the base uri.
   *
   * @param string $base_uri
   *   Base Elvis server uri with trailing slash.
   */
  public function setBaseUri($base_uri);

  /**
   * Set credentials data.
   *
   * @param string $username
   *   The username.
   * @param string $password
   *   The password.
   */
  public function setCredentialsData($username, $password);
}
