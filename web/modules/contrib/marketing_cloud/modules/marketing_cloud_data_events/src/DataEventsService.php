<?php

namespace Drupal\marketing_cloud_data_events;

use Drupal\marketing_cloud\MarketingCloudService;

/**
 * Class DataEventsService.
 *
 * For all of the API service calls, a correct JSON data payload is expected.
 * This is then validated against the JSON Schema. This approach minimises
 * any short-term issues with changes in the SF API, provides a sanitized
 * interface to send API calls and leaves flexibility for any modules that
 * want to use this as a base-module.
 *
 * @package Drupal\marketing_cloud
 */
class DataEventsService extends MarketingCloudService {

  private $moduleName = 'marketing_cloud_data_events';

  /**
   * Inserts a batch of data extensions rows by key.
   *
   * @param string $key
   *   Data extension external key, included in URL as key:your external key
   *   value here. Required if an ID is not provided.
   * @param array|object $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   The result of the API call.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/postDataExtensionRowsetByKey.htm
   */
  public function insertDataExtensionRowsByKey($key, $json = []) {
    $machineName = 'insert_data_extension_rows_by_key';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[key]' => $key]);
  }

  /**
   * Inserts a data extension row by key.
   *
   * @param string $key
   *   Data extension external key, included in URL as key:your external key
   *   value here. Required if an ID is not provided.
   * @param string $primaryKeys
   *   Key/Value pair of the primary key(s) for the row.
   * @param array|object $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   The result of the API call.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/putDataExtensionRowByKey.htm
   */
  public function insertDataExtensionRowByKey($key, $primaryKeys, $json = NULL) {
    $machineName = 'insert_data_extension_row_by_key';
    if (empty($json)) {
      $json = new \stdClass();
    }
    return $this->apiCall($this->moduleName, $machineName, $json, ['[key]' => $key, '[primaryKeys]' => $primaryKeys]);
  }

  /**
   * Increments a column value by data extension external key.
   *
   * @param string $key
   *   Data extension external key, included in URL as key:your external key.
   *   value here. Required if an ID is not provided.
   * @param string $primaryKeys
   *   Key/Value pair of the primary key(s) for the row.
   * @param string $column
   *   Column name to be incremented.
   * @param null|int $step
   *   Increment amount. If not present, default is 1.
   *
   * @return array|bool|null
   *   The result of the API call.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/putIncrementColumnValueByKey.htm
   */
  public function incrementColumnValueByDataExtensionKey($key, $primaryKeys, $column, $step = NULL) {
    $machineName = 'increment_column_value_by_data_extension_key';
    return $this->apiCall($this->moduleName,
      $machineName,
      new \stdClass(),
      ['[key]' => $key, '[primaryKeys]' => $primaryKeys, '[column]' => $column],
      (!empty($step) ? ['step' => $step] : [])
    );
  }

}
