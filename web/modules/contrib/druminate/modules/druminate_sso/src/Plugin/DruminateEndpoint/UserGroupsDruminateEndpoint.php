<?php

namespace Drupal\druminate_sso\Plugin\DruminateEndpoint;

use Drupal\druminate\Plugin\DruminateEndpointBase;

/**
 * Druminate Endpoint for SSO using the Login method.
 *
 * @DruminateEndpoint(
 *  id = "sso_user_groups",
 *  label = @Translation("LO Get User Groups Endpoint."),
 *  servlet = "CRConsAPI",
 *  method = "getUserGroups",
 *  authRequired = TRUE,
 *  cacheLifetime = 0,
 *  httpRequestMethod = "POST",
 *  params = {
 *    "response_format" = "json"
 *  }
 * )
 */
class UserGroupsDruminateEndpoint extends DruminateEndpointBase {
}
