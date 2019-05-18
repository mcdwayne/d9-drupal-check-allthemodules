<?php

namespace Drupal\druminate_sso\Plugin\DruminateEndpoint;

use Drupal\druminate\Plugin\DruminateEndpointBase;

/**
 * Druminate Endpoint for SSO using the Login method.
 *
 * @DruminateEndpoint(
 *  id = "sso_login",
 *  label = @Translation("LO SSO Login Endpoint."),
 *  servlet = "CRConsAPI",
 *  method = "login",
 *  authRequired = TRUE,
 *  cacheLifetime = 0,
 *  httpRequestMethod = "POST",
 *  params = {
 *    "response_format" = "json"
 *  }
 * )
 */
class SingleSignOnDruminateEndpoint extends DruminateEndpointBase {
}
