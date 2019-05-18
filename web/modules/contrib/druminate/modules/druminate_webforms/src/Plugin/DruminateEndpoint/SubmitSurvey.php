<?php

namespace Drupal\druminate_webforms\Plugin\DruminateEndpoint;

use Drupal\druminate\Plugin\DruminateEndpointBase;
use Drupal\druminate\Plugin\DruminateEndpointInterface;

/**
 * Calls the getCompanies method.
 *
 * @DruminateEndpoint(
 *  id = "submitSurvey",
 *  label = @Translation("Submit Survey Api."),
 *  servlet = "CRSurveyAPI",
 *  method = "submitSurvey",
 *  authRequired = TRUE,
 *  cacheLifetime = 0,
 *  httpRequestMethod = "post",
 *  params = {}
 * )
 */
class SubmitSurvey extends DruminateEndpointBase implements DruminateEndpointInterface {

}
