<?php

namespace Drupal\marketing_cloud_address;

use Drupal\marketing_cloud\MarketingCloudService;

/**
 * Class AddressService.
 *
 * For all of the API service calls, a correct JSON data payload is expected.
 * This is then validated against the JSON Schema. This approach minimises
 * any short-term issues with changes in the SF API, provides a sanitized
 * interface to send API calls and leaves flexibility for any modules that
 * want to use this as a base-module.
 *
 * @package Drupal\marketing_cloud
 */
class AddressService extends MarketingCloudService {

  private $moduleName = 'marketing_cloud_address';

  /**
   * Sends an email to a contact.
   *
   * @param array|object|string $json
   *   The JSON boy payload.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/validateEmail.htm
   */
  public function validateEmail($json) {
    $machineName = 'validate_email';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

}
