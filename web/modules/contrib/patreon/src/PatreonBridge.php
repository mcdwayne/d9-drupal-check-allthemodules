<?php

namespace Drupal\patreon;

/**
 * Class PatreonBridge.
 *
 * @package Drupal\patreon
 */
class PatreonBridge {

  /**
   * Constructs a new PatreonBridge object.
   */

  /**
   * PatreonBridge constructor.
   *
   * @param object $patreonAPI
   *   A valid Patreon API class object.
   * @param string $patreonUrl
   *   The domain of the current Patreon API.
   */
  public function __construct($patreonAPI, $patreonUrl) {
    $this->patreonAPI = $patreonAPI;
    $this->setApiUrl($patreonUrl);
  }

  /**
   * The API token for this Patreon Service API connection.
   *
   * @var object
   *    A valid Patreon API class object.
   */
  private $patreonAPI = '';

  /**
   * The domain address of the API.
   *
   * @var string
   *   A valid domain address.
   */
  private $apiUrl = '';

  /**
   * Get the current API domain.
   *
   * @return string
   *   A valid domain address.
   */
  public function getApiUrl() {
    return $this->apiUrl;
  }

  /**
   * Set the current API domain.
   *
   * @param string $url
   *   A valid domain address.
   */
  public function setApiUrl($url) {
    $this->apiUrl = $url;
  }

  /**
   * The API token for this Patreon Service API connection.
   *
   * @var string
   *    The API token for this Patreon Service API connection.
   */
  private $token = '';

  /**
   * The API refresh token for this Patreon Service API connection.
   *
   * @var string
   *    The API refresh token for this Patreon Service API connection.
   */
  private $refresh_token = '';

  /**
   * Bool to capture whether a token refresh has been tried.
   *
   * @var bool
   */
  public $refreshTried = FALSE;

  /**
   * Function to get the supplied token.
   *
   * @return string
   *   Returns the value of $this->token.
   *
   * @throws \Drupal\patreon\PatreonMissingTokenException
   */
  public function getToken() {
    if (!$this->token) {
      throw new PatreonMissingTokenException('An API token has not been set.');
    }
    return $this->token;
  }

  /**
   * Function to set the required token.
   *
   * @param string $token
   *   A valid token for the Patreon API.
   */
  public function setToken($token) {
    $this->token = $token;
  }

  /**
   * Function to get the supplied refresh token.
   *
   * @return string
   *   Returns the value of $this->refresh_token.
   *
   * @throws \Drupal\patreon\PatreonMissingTokenException
   */
  public function getRefreshToken() {
    if (!$this->refresh_token) {
      throw new PatreonMissingTokenException('An API refresh token has not been set.');
    }
    return $this->refresh_token;
  }

  /**
   * Function to set the required refresh token.
   *
   * @param string $token
   *   A valid refresh token for the Patreon API.
   */
  public function setRefreshToken($token) {
    $this->refresh_token = $token;
  }

  /**
   * Returns the URL to authorize an account to.
   *
   * @param string $clientId
   *   The client Id to authorise against.
   * @param string $redirectUrl
   *   The URL to redirect to after authorization.
   *
   * @return string
   *   A URL string.
   */
  public function getAuthoriseUrl($clientId, $redirectUrl) {
    return $this->getApiUrl() . '/oauth2/authorize?response_type=code&client_id=' . $clientId . '&redirect_uri=' . $redirectUrl;
  }

  /**
   * Helper to get refreshed tokens from the Patreon API.
   *
   * @param string $token
   *   A Patreon API refresh token.
   * @param string $redirect
   *   A valid Patreon API redirect URL.
   *
   * @return mixed
   *   Returns the tokens return from the API or an error.
   *
   * @throws \Drupal\patreon\PatreonGeneralException
   * @throws \Drupal\patreon\PatreonUnauthorizedException
   */
  public function getRefreshedTokens($token, $redirect) {
    $tokens = $this->patreonAPI->refresh_token($token, $redirect);

    if (array_key_exists('error', $tokens)) {
      if ($tokens['error'] == 'access_denied' || $tokens['error'] == 'invalid_grant') {
        throw new PatreonUnauthorizedException($tokens['error']);
      }
      else {
        throw new PatreonGeneralException($tokens['error']);
      }
    }

    return $tokens;
  }

  /**
   * Helper to return an access token from an Oauth code.
   *
   * @param string $code
   *   An oauth code returned by the Patreon API.
   * @param string $callback
   *   An valid Oauth callback URL.
   *
   * @return null|array
   *   An array of API tokens, or NULL in event of error.
   *
   * @throws \Drupal\patreon\PatreonGeneralException
   * @throws \Drupal\patreon\PatreonUnauthorizedException
   */
  public function tokensFromCode($code, $callback) {
    $tokens = $this->patreonAPI->get_tokens($code, $callback);

    if (array_key_exists('error', $tokens)) {
      if ($tokens['error'] == 'access_denied') {
        throw new PatreonUnauthorizedException($tokens['error']);
      }
      else {
        throw new PatreonGeneralException($tokens['error']);
      }
    }

    return $tokens;
  }

  /**
   * Helper function to query the Patreon API.
   *
   * @param object $client
   *   A valid Patreon API client.
   * @param string $function
   *   A valid Patreon API function.
   * @param array $parameters
   *   An array of parameters required for the function call. Defaults to empty.
   *
   * @return null|\Art4\JsonApiClient\Document
   *   The API callback return, or NULL on error.
   *
   * @throws \Drupal\patreon\PatreonGeneralException
   * @throws \Drupal\patreon\PatreonUnauthorizedException
   */
  public function apiFetch($client, $function, $parameters = array()) {
    $return = NULL;

    if (method_exists($client, $function)) {
      if ($parameters) {
        list($campaign_id, $page_size, $cursor) = $parameters;
        $api_response = $client->{$function}($campaign_id, $page_size, $cursor);
      }
      else {
        $api_response = $client->{$function}();
      }

      if (get_class($api_response) == 'Art4\JsonApiClient\Document') {
        if ($error = $this->getValueByKey($api_response, 'errors.0', TRUE)) {
          if (isset($error['status']) && $error['status'] == "401") {
            throw new PatreonUnauthorizedException('The Patreon API has returned an authorized response.');
          }

          throw new PatreonGeneralException('Patreon API has returned an unknown response.');
        }
        else {
          $return = $api_response;
        }
      }
      else {
        throw new PatreonGeneralException('Patreon API has returned an unknown response.');
      }
    }

    return $return;
  }

  /**
   * Helper to get a specified value from a Patreon API return.
   *
   * @param \Art4\JsonApiClient\Document|\Patreon\JSONAPI\ResourceItem $client
   *   A valid JSONAPIClient return from the Patreon API.
   * @param string $key
   *   A valid key to return data for.
   * @param bool $asArray
   *   Return the value using the Patreon API asarray method.
   *
   * @return mixed|null
   *   The value, or NULL on error.
   */
  public function getValueByKey($client, $key, $asArray = FALSE) {
    $return = NULL;

    if ($client->has($key)) {
      if ($asArray) {
        $return = $client->get($key)->asArray();
      }
      else {
        $return = $client->get($key);
      }
    }

    return $return;
  }

  /**
   * Helper to check if a Patreon user is a patron of the client.
   *
   * @param \Art4\JsonApiClient\Document $patreon_return
   *   Return from patreon_fetch_user().
   * @param string $creator_id
   *   The ID of the creator account on Patreon.
   *
   * @return bool
   *   TRUE is user's pledges match creator id. Defaults to FALSE.
   */
  public function isPatron(\Art4\JsonApiClient\Document $patreon_return, $creator_id) {
    if ($user = $this->getValueByKey($patreon_return, 'data')) {
      if ($related = $this->getValueByKey($user, 'relationships.pledges.data')) {
        foreach ($related->getKeys() as $rkey) {
          if ($pledge = $user->relationship('pledges')->get($rkey)->resolve($patreon_return)) {
            if ($pledge_creator = $this->getValueByKey($pledge, 'relationships.creator.id')) {
              if ($pledge_creator == $creator_id) {
                return TRUE;
              }
            }
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * Helper to get a user's pledges.
   *
   * @param \Art4\JsonApiClient\Document $patreon_return
   *   Return from patreon_fetch_user().
   *
   * @return array
   *   An array of all pledges.
   */
  public function getPatronPledges(\Art4\JsonApiClient\Document $patreon_return) {
    $return = [];
    if ($user = $this->getValueByKey($patreon_return, 'data')) {
      if ($related = $this->getValueByKey($user, 'relationships.pledges.data')) {
        foreach ($related->getKeys() as $rkey) {
          if ($pledge = $user->relationship('pledges')->get($rkey)->resolve($patreon_return)) {
            $return[] = $pledge;
          }
        }
      }
    }

    return $return;
  }

  /**
   * Helper to find if a Patreon User has been deleted or blocked.
   *
   * @param \Art4\JsonApiClient\Document $patreon_account
   *   Return from a patreon_fetch_user().
   * @param string $drupal_account_name
   *   A valid Drupal user account name.
   *
   * @return bool
   *   Returns TRUE if any value
   */
  public function isDeletedUser(\Art4\JsonApiClient\Document $patreon_account, $drupal_account_name) {
    $return = $this->getValueByKey($patreon_account, 'is_deleted') == TRUE ||
      $this->getValueByKey($patreon_account, 'is_nuked') == TRUE ||
      $suspended = $this->getValueByKey($patreon_account, 'is_suspended') == TRUE ||
        $blocked = user_is_blocked($drupal_account_name) == TRUE;

    return $return;
  }

  /**
   * Helper to make all campaigns into Drupal roles.
   *
   * @param \Art4\JsonApiClient\Document $campaigns
   *   A return campaign endpoint.
   *
   * @return array
   *   An array of reward titles plus default roles.
   */
  public function getPatreonRoleNames(\Art4\JsonApiClient\Document $campaigns) {
    $roles = [
      'Patreon User' => NULL,
      'Deleted Patreon User' => NULL,
    ];

    if ($campaign_data = $this->getValueByKey($campaigns, 'data')) {
      foreach ($campaign_data->getKeys() as $campaign_key) {
        $campaign = $this->getValueByKey($campaign_data, $campaign_key);

        foreach ($this->getRelatedRewards($campaign, $campaigns) as $reward) {
          if ($attributes = $this->getValueByKey($reward, 'attributes')) {
            if ($title = $this->getValueByKey($attributes, 'title')) {
              $id = $this->getValueByKey($reward, 'id');
              $roles[$title . ' Patron'] = $id;
            }
          }
        }
      }
    }

    return $roles;
  }

  /**
   * Returns an array of related rewards from a API return.
   *
   * @param \Art4\JsonApiClient\Document $document
   *   A single item from the full API return to obtain related rewards for.
   * @param \Art4\JsonApiClient\Document $api_response
   *   The original API response containing $document.
   *
   * @return array
   *   An array of rewards.
   */
  public function getRelatedRewards($document, $api_response) {
    $return = array();

    if ($related = $this->getValueByKey($document, 'relationships.rewards.data')) {
      foreach ($related->getKeys() as $rkey) {
        if ($reward = $document->relationship('rewards')->get($rkey)->resolve($api_response)) {
          $return[] = $reward;
        }
      }
    }

    return $return;
  }

  /**
   * Helper to make a username unique if it exists.
   *
   * @param string $name
   *   A Patreon user's full name.
   * @param string $patreon_id
   *   A Patreon user's patreon id.
   *
   * @return string
   *   A deduped username if required. Defaults to provided.
   */
  public function getUniqueUserName($name, $patreon_id) {
    if ($existing_name = user_load_by_name($name)) {
      $name .= '_' . $patreon_id;
    }

    // By rights, if the combination of Patreon Fullname and Patreon ID already
    // exists as a username, it must have been because of this function. But to
    // reach this point, we have already failed to find the user by their email
    // address so we will deduplicate the user name to be sure we do not expose
    // private data to the wrong people.
    if ($existing_name = user_load_by_name($name)) {
      $key = 0;

      while ($existing_name = user_load_by_name($name . '_' . $key)) {
        $key++;
      }

      $name = $name . '_' . $key;

    }

    return $name;
  }

  /**
   * Helper to collate all pledge data for a pledge return.
   *
   * @param \Art4\JsonApiClient\Document $pledges_response
   *   Response from the 'fetch_page_of_pledges' function.
   * @param int $campaign_id
   *   The id of the campaign the pledges relate to.
   *
   * @return array
   *   An array of resuilt: all data, total amount and count of pledges.
   */
  public function getPledgeData(\Art4\JsonApiClient\Document $pledges_response, $campaign_id) {
    $count = 0;
    $amount = 0;
    $all_data = [];

    foreach ($pledges_response->get('data')->getKeys() as $pledge_data_key) {
      $pledge_data = $pledges_response->get('data')->get($pledge_data_key);
      $pledge_amount = $this->getValueByKey($pledge_data, 'attribute.amount_cents');
      $patron = $pledge_data->relationship('patron')->resolve($pledges_response);
      $patron_id = $this->getValueByKey($patron, 'id');
      $patron_full_name = $this->getValueByKey($patron, 'attribute.full_name');
      $count++;
      $amount += $pledge_amount;
      $all_data[$patron_id] = [
        'name' => $patron_full_name,
        'amount_' . $campaign_id => $pledge_amount,
      ];
    }

    return [
      'all_data' => $all_data,
      'count' => $count,
      'amount' => $amount,
    ];
  }

}
