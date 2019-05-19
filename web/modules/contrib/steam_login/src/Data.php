<?php

namespace Drupal\steam_login;

/**
 * Mutualize steam data.
 */
class Data {

  const STEAM_OPENID_URL = 'https://steamcommunity.com/openid/';

  const STEAM_OPENID_COMMUNITYID_REGEX = '/^https:\/\/steamcommunity\.com\/openid\/id\/(7[0-9]{15,25}+)$/';

}
