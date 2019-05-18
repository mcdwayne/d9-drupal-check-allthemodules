<?php

namespace Drupal\marketing_cloud_platform;

use Drupal\marketing_cloud\MarketingCloudService;

/**
 * Class PlatformService.
 *
 * For all of the API service calls, a correct JSON data payload is expected.
 * This is then validated against the JSON Schema. This approach minimises
 * any short-term issues with changes in the SF API, provides a sanitized
 * interface to send API calls and leaves flexibility for any modules that
 * want to use this as a base-module.
 *
 * @package Drupal\marketing_cloud
 */
class PlatformService extends MarketingCloudService {

  private $moduleName = 'marketing_cloud_platform';

  /**
   * Gets endpoint data.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getendpoints.htm
   */
  public function getEndpoints() {
    $machineName = 'get_endpoints';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass());
  }

  /**
   * Returns information about the authenticated token.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/gettokencontext.htm
   */
  public function getTokenContext() {
    $machineName = 'get_token_context';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass());
  }

  /**
   * Gets endpoint data.
   *
   * @param string $endpointType
   *   Value of endpointType. Valid endpoints are ftp, soap, and rest.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getendpoint.htm
   */
  public function getEndpoint($endpointType) {
    $machineName = 'get_endpoint';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[endpointType]' => $endpointType]);
  }

}
