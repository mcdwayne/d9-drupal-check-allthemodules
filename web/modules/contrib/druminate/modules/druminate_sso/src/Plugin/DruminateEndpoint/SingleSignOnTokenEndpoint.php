<?php

namespace Drupal\druminate_sso\Plugin\DruminateEndpoint;

use Drupal\druminate\Plugin\DruminateEndpointBase;

/**
 * Druminate Endpoint for SSO using the getSingleSignOnToken method.
 *
 * @DruminateEndpoint(
 *  id = "sso_token",
 *  label = @Translation("LO SSO Token Endpoint."),
 *  servlet = "SRConsAPI",
 *  method = "getSingleSignOnToken",
 *  authRequired = TRUE,
 *  cacheLifetime = 0,
 *  httpRequestMethod = "POST",
 *  params = {
 *    "response_format" = "json"
 *  }
 * )
 */
class SingleSignOnTokenEndpoint extends DruminateEndpointBase {
}
