<?php

namespace Drupal\marketing_cloud_push;

use Drupal\marketing_cloud\MarketingCloudService;

/**
 * Class PushService.
 *
 * For all of the API service calls, a correct JSON data payload is expected.
 * This is then validated against the JSON Schema. This approach minimises
 * any short-term issues with changes in the SF API, provides a sanitized
 * interface to send API calls and leaves flexibility for any modules that
 * want to use this as a base-module.
 *
 * @package Drupal\marketing_cloud
 */
class PushService extends MarketingCloudService {

  private $moduleName = 'marketing_cloud_push';

  /**
   * Creates a push message. That request optionally allows you to pass the text of the message to override the message specified in the definition.
   *
   * @param array|object|string $json
   *   The body JSON payload.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/createPushMessage.htm
   */
  public function createPushMessage($json) {
    $machineName = 'create_push_message';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Retrieves all messages currently defined within an account.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/retrieveAllPushMessages.htm
   */
  public function getPushMessages() {
    $machineName = 'get_push_messages';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass());
  }

  /**
   * Creates a new location.
   *
   * @param array|object|string $json
   *   The body JSON payload.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/createLocation.htm
   */
  public function createLocation($json) {
    $machineName = 'create_location';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Gets a list of all locations.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getLocations.htm
   */
  public function getLocations() {
    $machineName = 'get_locations';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass());
  }

  /**
   * Retrieves a single app associated with an account.
   *
   * @param string $appId
   *   String value identifying the app.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/retrieveAppInformation.htm
   */
  public function getAppInfo($appId) {
    $machineName = 'get_app_info';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[appId]' => $appId]);
  }

  /**
   * Updates a push message. That request optionally allows you to pass the text of the message to override the message specified in the definition.
   *
   * @param string $messageId
   *   Id of the message to update.
   * @param array|object|string $json
   *   The body JSON payload.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/updatePushMessage.htm
   */
  public function updatePushMessage($messageId, $json) {
    $machineName = 'update_push_message';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[messageId]' => $messageId]);
  }

  /**
   * Deletes an existing message.
   *
   * @param string $messageId
   *   Id of the message to delete.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/deletePushMessage.htm
   */
  public function deletePushMessage($messageId) {
    $machineName = 'delete_push_message';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[messageId]' => $messageId]);
  }

  /**
   * Retrieves a single message currently defined within an account.
   *
   * @param string $messageId
   *   Id of the message to retrieve.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/retrieveSinglePushMessage.htm
   */
  public function getPushMessage($messageId) {
    $machineName = 'get_push_message';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[messageId]' => $messageId]);
  }

  /**
   * Gets a specific location.
   *
   * @param string $locationId
   *   ID of the location.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getSpecificLocation.htm
   */
  public function getSpecificLocation($locationId) {
    $machineName = 'get_specific_location';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[locationId]' => $locationId]);
  }

  /**
   * Updates an existing location.
   *
   * @param string $locationId
   *   ID of the location to update.
   * @param array|object|string $json
   *   The body JSON payload.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/updateLocation.htm
   */
  public function updateLocation($locationId, $json) {
    $machineName = 'update_location';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[locationId]' => $locationId]);
  }

  /**
   * Deletes an existing location.
   *
   * @param string $locationId
   *   ID of the location to delete.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/deleteLocation.htm
   */
  public function deleteLocation($locationId) {
    $machineName = 'delete_location';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[locationId]' => $locationId]);
  }

  /**
   * Retrieves all custom key values associated with an app.
   *
   * @param string $appId
   *   String value identifying the app.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/retrieveAllCustomKeyValues.htm
   */
  public function getCustomKeys($appId) {
    $machineName = 'get_custom_keys';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[appId]' => $appId]);
  }

  /**
   * Updates information on all custom key values associated with an app.
   *
   * @param string $appId
   *   String value identifying the app.
   * @param array|object|string $json
   *   The body JSON payload.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/updateAllCustomKeyValues.htm
   */
  public function updateCustomKeys($appId, $json) {
    $machineName = 'update_custom_keys';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[appId]' => $appId]);
  }

  /**
   * Deletes all custom key values associated with an app.
   *
   * @param string $appId
   *   String value identifying the app.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/deleteAllCustomKeyValues.htm
   */
  public function deleteCustomKeys($appId) {
    $machineName = 'delete_custom_keys';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[appId]' => $appId]);
  }

  /**
   * Refreshes a list.
   *
   * @param string $id
   *   The Id of the list to refresh.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/postRefreshAudience.htm
   */
  public function refreshList($id) {
    $machineName = 'refresh_list';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[id]' => $id]);
  }

  /**
   * Broadcasts a message to all users of a push-enabled app.
   *
   * @param string $messageId
   *   The ID of the triggered send definition configured for MessageSend
   *   sending used for the send.
   * @param array|object|string $json
   *   The body JSON payload.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/postMessageAppSend.htm
   */
  public function sendMessageToAll($messageId, $json) {
    $machineName = 'send_message_to_all';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[messageId]' => $messageId]);
  }

  /**
   * Sends a push message to users with the specified tags.
   *
   * @param string $messageId
   *   The ID of the triggered send definition configured for MessageSend
   *   sending used for the send.
   * @param array|object|string $json
   *   The body JSON payload.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/postMessageTagSend.htm
   */
  public function sendMessageToTaggedUsers($messageId, $json) {
    $machineName = 'send_message_to_tagged_users';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[messageId]' => $messageId]);
  }

  /**
   * Sends a message to the specified mobile devices of a push-enabled app.
   *
   * @param string $messageId
   *   required, The API key of the message definition (configured in the
   *   MobileConnect user interface.
   * @param array|object|string $json
   *   The body JSON payload.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/postMessageListSend.htm
   */
  public function sendMessageToList($messageId, $json) {
    $machineName = 'send_message_to_list';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[messageId]' => $messageId]);
  }

  /**
   * Updates information on a single custom key value associated with an app.
   *
   * @param string $appId
   *   String value identifying the app.
   * @param string $key
   *   String value identifying the key (must be less than or equal to 15
   *   characters).
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/updateSpecificCustomKeyValue.htm
   */
  public function updateCustomKey($appId, $key) {
    $machineName = 'update_custom_key';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[appId]' => $appId, '[key]' => $key]);
  }

  /**
   * Deletes a single custom key value associated with an app.
   *
   * @param string $appId
   *   String value identifying the app.
   * @param string $key
   *   String value identifying the key. The string must be less than or equal
   *   to 15 characters.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/deleteSpecificCustomKeyValue.htm
   */
  public function deleteCustomKey($appId, $key) {
    $machineName = 'delete_custom_key';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[appId]' => $appId, '[key]' => $key]);
  }

  /**
   * Ends unique messages to devices within the same API call. Each batch can include a maximum of 5000 subscriber key or device token values, depending on which value the call uses.
   *
   * @param string $messageId
   *   The ID of the message to update.
   * @param array|object|string $json
   *   The body JSON payload.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/postMessageBatch.htm
   */
  public function sendMessageToMobileDevicesInBatch($messageId, $json) {
    $machineName = 'send_messages_to_mobile_devices_in_batch';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[messageId]' => $messageId]);
  }

  /**
   * Sends a message to the specified mobile devices of a push-enabled app.
   *
   * @param string $messageId
   *   The ID of the triggered send definition configured for MessageSend
   *   sending used for the send.
   * @param array|object|string $json
   *   The body JSON payload.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/postMessageContactSendPush.htm
   */
  public function sendMessageToMobileDevices($messageId, $json) {
    $machineName = 'send_message_to_mobile_devices';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[messageId]' => $messageId]);
  }

  /**
   * Gets the refresh list status.
   *
   * @param string $id
   *   The ID of the list in MobileConnect.
   * @param string $tokenId
   *   The unique ID returned when using the RefreshList operation.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getRefreshAudience.htm
   */
  public function getRefreshListStatus($id, $tokenId) {
    $machineName = 'get_refresh_list_status';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[id]' => $id, '[tokenId]' => $tokenId]);
  }

  /**
   * Retrieves delivery status of a previous messageApp send job.
   *
   * @param string $messageId
   *   The API key of the message definition.
   *   The key is configured in the MobilePush user interface.
   * @param string $tokenId
   *   The value returned following the send of a push message.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getMessageAppDeliveries.htm
   */
  public function getDeliveryStatusOfMessageApp($messageId, $tokenId) {
    $machineName = 'get_delivery_status_of_message_app';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[messageId]' => $messageId, '[tokenId]' => $tokenId]);
  }

  /**
   * Retrieves delivery status of a previous messageTag send job.
   *
   * @param string $messageId
   *   The API key of the message definition.
   *   This key is configured in the MobilePush user interface.
   * @param string $tokenId
   *   The value returned following the send of a push message.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getMessageTagDeliveries.htm
   */
  public function getDeliveryStatusOfMessageTag($messageId, $tokenId) {
    $machineName = 'get_delivery_status_of_message_tag';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[messageId]' => $messageId, '[tokenId]' => $tokenId]);
  }

  /**
   * Retrieves delivery status of a previous messageList send job.
   *
   * @param string $messageId
   *   The API key of the message definition.
   *   The key is configured in the MobilePush user interface.
   * @param string $tokenId
   *   The value returned following the send of a push message.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getMessageListDeliveries.htm
   */
  public function getDeliveryStatusOfMessageList($messageId, $tokenId) {
    $machineName = 'get_delivery_status_of_message_list';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[messageId]' => $messageId, '[tokenId]' => $tokenId]);
  }

  /**
   * Retrieves delivery status of a previous messageContact send job.
   *
   * @param string $messageId
   *   Message Id provided for the messageContact.
   * @param string $tokenId
   *   Token Id returned for the messageContact in the form of a GUID.
   *
   * @return array|bool|null
   *   The result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getMessageContactDeliveries.htm
   */
  public function getDeliveryStatusOfMessageContact($messageId, $tokenId) {
    $machineName = 'get_delivery_status_of_message_contact';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[messageId]' => $messageId, '[tokenId]' => $tokenId]);
  }

}
