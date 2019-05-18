<?php

namespace Drupal\marketing_cloud_assets;

use Drupal\marketing_cloud\MarketingCloudService;

/**
 * Class AssetsService.
 *
 * For all of the API service calls, a correct JSON data payload is expected.
 * This is then validated against the JSON Schema. This approach minimises
 * any short-term issues with changes in the SF API, provides a sanitized
 * interface to send API calls and leaves flexibility for any modules that
 * want to use this as a base-module.
 *
 * @package Drupal\marketing_cloud
 */
class AssetsService extends MarketingCloudService {

  private $moduleName = 'marketing_cloud_assets';

  /**
   * Gets an asset collection by simple $filter parameters.
   *
   * @param array $params
   *   URL filter params. Permissible values:
   *     $page number Page number to return from the paged results. Start with
   *       1 and continue until you get zero results. Typically provided along
   *       with the $pagesize parameter.
   *     $pagesize number Number of results per page to return. Typically
   *       provided along with the $page parameter.
   *     $orderBy string Determines which asset property to use for sorting,
   *       and also determines the direction in which to sort the data. If you
   *       don't provide the $orderBy parameter, the results are sorted by
   *       asset ID in ascending order.
   *     $filter string Filter by an asset's property using a simple operator
   *       and value.
   *     $fields string Comma delimited string of asset properties used to
   *       reduce the size of your results to only the properties you need.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/assetSimpleQuery.htm
   */
  public function simpleQuery(array $params) {
    $machineName = 'simple_query';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), [], $params);
  }

  /**
   * Gets an asset collection by advanced query.
   *
   * @param array|object|string $json
   *   The payload for the JSON body.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/assetAdvancedQuery.htm
   */
  public function advancedQuery($json) {
    $machineName = 'advanced_query';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Creates a category (folder) in Content Builder.
   *
   * @param array|object|string $json
   *   The payload for the JSON body.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/createCategory.htm
   */
  public function createCategory($json) {
    $machineName = 'create_category';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Returns one or more Content Builder categories that are owned by or reside in your MID. To request categories that have been shared with your MID, add a scope parameter to the call.
   *
   * @param array $params
   *   URL filter params. Permissible values:
   *     $filter string Filter by ParentId using a simple operator and value.
   *       ParentId is the only allowed field. If you don't provide a $filter
   *       parameter, the query returns all the Categories in your MID.
   *     $page number Page number to return from the paged results. Start with
   *       1 and continue until you get zero results. Typically provided along
   *       with the $pagesize parameter.
   *     $pagesize number Number of results per page to return. Typically
   *       provided along with the $page parameter.
   *     $orderBy string Determines which category property to use for sorting,
   *       and also determines the direction in which to sort the data. If you
   *       don't provide the $orderBy parameter, the results are sorted by
   *       category ID in ascending order.
   *     scope string Determines which MIDs the query results come from. To
   *       return categories that reside in your MID, either don't add the ]
   *       scope parameter or call the endpoint like this:
   *       .../categories?scope=Ours. To return categories that are shared to
   *       your MID, or that you have shared with other MIDs, call the endpoint
   *       like this: .../categories?scope=Shared. To return all categories
   *       visible to your MID, call the endpoint like this:
   *       .../categories?scope=Ours,Shared.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getCategories.htm
   */
  public function getCategories(array $params) {
    $machineName = 'get_categories';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), [], $params);
  }

  /**
   * Returns one Content Builder category by ID.
   *
   * @param int $id
   *   The ID of the category.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getCategory.htm
   */
  public function getCategoryById($id) {
    $machineName = 'get_category_by_id';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[id]' => $id]);
  }

  /**
   * Updates one Content Builder category by ID.
   *
   * @param int $id
   *   The ID of the category.
   * @param array|object|string $json
   *   The payload for the JSON body.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/updateCategory.htm
   */
  public function updateCategoryById($id, $json) {
    $machineName = 'update_category_by_id';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[id]' => $id]);
  }

  /**
   * Deletes one Content Builder category by ID.
   *
   * @param int $id
   *   The ID of the category.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/deleteCategory.htm
   */
  public function deleteCategoryById($id) {
    $machineName = 'delete_category_by_id';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[id]' => $id]);
  }

}
