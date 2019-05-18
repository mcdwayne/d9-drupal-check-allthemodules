<?php

namespace Drupal\bynder;

use Symfony\Component\HttpFoundation\Request;

/**
 * Provides Drupal 8 Bynder API.
 *
 * @package Drupal\bynder
 */
interface BynderApiInterface {

  /**
   * Gets an instance of the asset bank manager to use for DAM queries.
   *
   * @return \Bynder\Api\IAssetBankManager
   *   An instance of the asset bank manager using the request handler.
   */
  public function getAssetBankManager();

  /**
   * Initiates the access token retrieval.
   *
   * Gets request token from Bynder and prepares everything to redirect user to
   * Bynder for login.
   *
   * @see ::finishOAuthTokenRetrieval()
   *
   * @return \Drupal\Core\Url
   *   Url to redirect the user to.
   */
  public function initiateOAuthTokenRetrieval();

  /**
   * Finishes the access token retrieval after the user has been redirected.
   *
   * When Bynder redirects the user back after the successful login this
   * function takes over and gets the access token and stores it for the future
   * use.
   *
   * @param Request $request
   *   The current request.
   */
  public function finishOAuthTokenRetrieval(Request $request);

  /**
   * Gets if the current user has a valid oAuth access token.
   *
   * @return bool
   *   TRUE if the current user has a valid oAuth access token. FALSE otherwise.
   */
  public function hasAccessToken();

  /**
   * Checks if the current user has upload permissions.
   *
   * @return string|false
   *   Name of the user's upload role if uploads are allowed and FALSE
   *   otherwise.
   */
  public function hasUploadPermissions();

  /**
   * Sets the Bynder configuration.
   *
   * @param array $config
   *   Array with keys consumer_key, consumer_secret, token, token_secret and account_domain.
   *
   * @return bool
   */
  public function setBynderConfiguration(array $config);

  /**
  * Creates an asset usage entry in Bynder.
  *
  * @param string $asset_id
  *    Bynder asset ID.
  * @param \Drupal\Core\Url $usage_url
  *    Url where the asset is being used (node url).
  * @param string $creation_date
  *    Date the asset was added to the page, in the DATE_ISO8601 format.
  * @param string $additional_info
  *    Any additional info to be displayed with the entry information.
  *
  * @return mixed
  */
  public function addAssetUsage($asset_id, $usage_url, $creation_date, $additional_info = null);


  /**
   * Removes asset usage entry for a specific Bynder asset.
   *
   * @param $asset_id
   *    Bynder asset ID.
   * @param null $usage_url
   *    Url where the asset is being used (node url).
   *
   * @return mixed
   */
  public function removeAssetUsage($asset_id, $usage_url = null);

  /**
   * Retrieves all asset usage for a specific asset.
   *
   * @param $asset_id
   *    Bynder asset ID.
   *
   * @return mixed
   */
  public function getAssetUsages($asset_id);


}
