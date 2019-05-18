<?php

namespace Drupal\marketing_cloud_campaigns;

use Drupal\marketing_cloud\MarketingCloudService;

/**
 * Class CampaignsService.
 *
 * For all of the API service calls, a correct JSON data payload is expected.
 * This is then validated against the JSON Schema. This approach minimises
 * any short-term issues with changes in the SF API, provides a sanitized
 * interface to send API calls and leaves flexibility for any modules that
 * want to use this as a base-module.
 *
 * @package Drupal\marketing_cloud
 */
class CampaignsService extends MarketingCloudService {

  private $moduleName = 'marketing_cloud_campaigns';

  /**
   * Creates a campaign.
   *
   * @param array|object|string $json
   *   The JSON body payload.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/createCampaign.htm
   */
  public function createCampaign($json) {
    $machineName = 'create_campaign';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Retrieves a collection of campaigns.
   *
   * @param array $params
   *   Values for refining the search. valid key/val are:
   *     page integer Page number of data returned. The default value is 1.
   *     pageSize integer Number of records per page. The maximum and default
   *       value is 50.
   *     orderBy string Defines the order of the data (Default value of
   *       ModifiedDate DESC). Valid values include ModifiedDate, Name,
   *       CreatedDate, ID. All values must include either ASC (for ascending)
   *       or DESC (for descending) following the actual value. ASC and DESC
   *       indicate the order in which the specified information appears.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getCampaignCollection.htm
   */
  public function getCampaignCollection(array $params) {
    $machineName = 'get_campaign_collection';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), [], $params);
  }

  /**
   * Retrieves a campaign.
   *
   * @param string $id
   *   Campaign ID.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getCampaign.htm
   */
  public function getCampaign($id) {
    $machineName = 'get_campaign';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[id]' => $id]);
  }

  /**
   * Deletes a campaign.
   *
   * @param string $id
   *   Campaign ID.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/deleteCampaign.htm
   */
  public function deleteCampaign($id) {
    $machineName = 'delete_campaign';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[id]' => $id]);
  }

  /**
   * Associates an asset or collection of assets to a campaign.
   *
   * @param string $id
   *   Campaign ID.
   * @param array|object|string $json
   *   The JSON body payload.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/associateAssetToCampaign.htm
   */
  public function associateAssetToCampaign($id, $json) {
    $machineName = 'associate_asset_to_campaign';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[id]' => $id]);
  }

  /**
   * Retrieves a collection of campaign assets.
   *
   * @param string $id
   *   Asset ID.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getCampaignAssetCollection.htm
   */
  public function getCollectionOfCampaignAssets($id) {
    $machineName = 'get_collection_of_campaign_assets';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[id]' => $id]);
  }

  /**
   * Retrieves a campaign asset.
   *
   * @param string $id
   *   Campaign ID.
   * @param string $assetId
   *   Asset ID.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getCampaignAsset.htm
   */
  public function getCampaignAsset($id, $assetId) {
    $machineName = 'get_campaign_asset';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[id]' => $id, '[assetId]' => $assetId]);
  }

  /**
   * Disassociates an asset from a campaign.
   *
   * @param string $id
   *   Campaign ID.
   * @param string $assetId
   *   Asset ID.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/unassociateAssetToCampaign.htm
   */
  public function unassociateAssetFromCampaign($id, $assetId) {
    $machineName = 'unassociate_asset_from_campaign';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[id]' => $id, '[assetId]' => $assetId]);
  }

}
