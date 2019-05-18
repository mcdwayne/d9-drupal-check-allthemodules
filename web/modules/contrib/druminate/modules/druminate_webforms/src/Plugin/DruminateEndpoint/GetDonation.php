<?php

namespace Drupal\druminate_webforms\Plugin\DruminateEndpoint;

use Drupal\druminate\Plugin\DruminateEndpointBase;
use Drupal\druminate\Plugin\DruminateEndpointInterface;

/**
 * Calls the getSurvey method.
 *
 * @DruminateEndpoint(
 *  id = "getDonationForm",
 *  label = @Translation("Get Donation Form Information."),
 *  servlet = "CRDonationAPI",
 *  method = "getDonationFormInfo",
 *  authRequired = TRUE,
 *  cacheLifetime = 0,
 *  params = {}
 * )
 */
class GetDonation extends DruminateEndpointBase implements DruminateEndpointInterface {

}
