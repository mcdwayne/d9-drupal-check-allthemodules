<?php

namespace Drupal\patreon;

use \Drupal\user\UserInterface;

/**
 * Interface PatreonServiceInterface.
 *
 * @package Drupal\patreon
 */
interface PatreonServiceInterface {

  /**
   * Helper to return the valid absolute Oauth Callback URL.
   *
   * @return \Drupal\Core\Url
   *   The absolute URL of the Oaith Callback route,
   */
  public function getCallback();

  /**
   * Redirect a user to authorise access to their Patreon account.
   *
   * @param string $client_id
   *   The client idea of the Patreon creator.
   * @param bool $redirect
   *   If TRUE, redirects the user to the generated URL. If not, returns URL.
   *
   * @return \Drupal\Core\Url|bool
   *   Returns the generated URL if $redirect set to FALSE, else FALSE on error.
   */
  public function authoriseAccount($client_id, $redirect = TRUE);

  /**
   * Helper to return an access token from an Oauth code.
   *
   * @param string $code
   *   An oauth code returned by the Patreon API.
   *
   * @return null|string
   *   A valid access token, or NULL in event of error.
   *
   * @throws \Drupal\patreon\PatreonGeneralException
   * @throws \Drupal\patreon\PatreonUnauthorizedException
   */
  public function tokensFromCode($code);

  /**
   * Store the tokens provided by the Patreon Oauth API.
   *
   * @param array $tokens
   *   An array of tokens returned by the API.
   * @param \Drupal\user\UserInterface $account
   *   The account of the user storing the tokens. Optional.
   */
  public function storeTokens($tokens, UserInterface $account = NULL);

  /**
   * Load the tokens stored by $this->storeTokens().
   *
   * @param \Drupal\user\UserInterface $account
   *   The account of the user requesting the tokens. Optional.
   *
   * @return array
   *   An array of tokens.
   */
  public function getStoredTokens(UserInterface $account = NULL);

  /**
   * Helper to return user data from the Patreon API.
   *
   * @return null|array
   *   An array of data from the Patreon API, or NULL on error.
   */
  public function fetchUser();

  /**
   * Helper to return campaign data from the Patreon API.
   *
   * @return null|array
   *   An array of data from the Patreon API, or NULL on error.
   */
  public function fetchCampaign();

  /**
   * Fetch a paged list of pledge data from the Patreon API.
   *
   * @param int $campaign_id
   *   A valid Patreon campaign id.
   * @param int $page_size
   *   The number of items per page.
   * @param null|string $cursor
   *   A cursor chracter.
   *
   * @return null|array
   *   An array of data from the Patreon API or NULL on error.
   */
  public function fetchPagePledges($campaign_id, $page_size, $cursor = NULL);

}
