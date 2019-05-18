<?php

/**
 * @file
 * Documents hooks provided by the OAuth2 Client module.
 */

/**
 * Alter OAuth2 Client plugin values.
 *
 * @param array $oauth2_clients
 *   An array of OAuth2 Client plugins registered on the system.
 */
function hook_oauth2_client_info_alter(array &$oauth2_clients) {
  $oauth2_clients['client_id']['some_key'] = 'some_value';
}
