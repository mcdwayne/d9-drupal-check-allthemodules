<?php

namespace Drupal\druminate_webforms\Plugin\DruminateEndpoint;

use Drupal\druminate\Plugin\DruminateEndpointBase;
use Drupal\druminate\Plugin\DruminateEndpointInterface;

/**
 * Calls the getSurvey method.
 *
 * @DruminateEndpoint(
 *  id = "getSurvey",
 *  label = @Translation("List Survey Api."),
 *  servlet = "CRSurveyAPI",
 *  method = "getSurvey",
 *  authRequired = TRUE,
 *  cacheLifetime = 0,
 *  params = {}
 * )
 */
class GetSurvey extends DruminateEndpointBase implements DruminateEndpointInterface {

}
