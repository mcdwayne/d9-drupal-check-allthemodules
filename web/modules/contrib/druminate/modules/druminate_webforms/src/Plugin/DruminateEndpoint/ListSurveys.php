<?php

namespace Drupal\druminate_webforms\Plugin\DruminateEndpoint;

use Drupal\druminate\Plugin\DruminateEndpointBase;
use Drupal\druminate\Plugin\DruminateEndpointInterface;

/**
 * Calls the listSurveys method.
 *
 * @DruminateEndpoint(
 *  id = "listSurveys",
 *  label = @Translation("List Survey Api."),
 *  servlet = "CRSurveyAPI",
 *  method = "listSurveys",
 *  authRequired = FALSE,
 *  cacheLifetime = 0,
 *  params = {}
 * )
 */
class ListSurveys extends DruminateEndpointBase implements DruminateEndpointInterface {

}
