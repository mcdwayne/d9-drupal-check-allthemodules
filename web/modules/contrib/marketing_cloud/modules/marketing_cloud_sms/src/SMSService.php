<?php

namespace Drupal\marketing_cloud_sms;

use Drupal\marketing_cloud\MarketingCloudService;

/**
 * Class SMSService.
 *
 * For all of the API service calls, a correct JSON data payload is expected.
 * This is then validated against the JSON Schema. This approach minimises
 * any short-term issues with changes in the SF API, provides a sanitized
 * interface to send API calls and leaves flexibility for any modules that
 * want to use this as a base-module.
 *
 * @package Drupal\marketing_cloud
 */
class SMSService extends MarketingCloudService {

  private $moduleName = 'marketing_cloud_sms';

  /**
   * Creates a keyword on an account.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/createKeyword.htm
   */
  public function createKeyword($json) {
    $machineName = 'create_keyword';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Queues an MO message for send.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/postQueueMO.htm
   */
  public function queueMoMessage($json) {
    $machineName = 'queue_mo_message';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Creates an SMS opt-in message permitting contacts to subscribe to further SMS messages.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/messageOptIn.htm
   */
  public function createOptinMessage($json) {
    $machineName = 'create_optin_message';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Deletes a keyword on an account given a keyword Id.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/deleteKeywordViaKeywordId.htm
   */
  public function deleteKeywordById($json) {
    $machineName = 'delete_keyword_by_id';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Initiates a message to one or more contact lists.
   *
   * @param string $messageId
   *   The encodedID can be found when creating a â€œAPI Entry Eventâ€ type
   *   Outbound message in the UI. If you have already passed that point you
   *   can find the ID by looking at the API resource behind the scenes when
   *   you open that message in the UI.
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/postMessageList.htm
   */
  public function postMessageToList($messageId, $json) {
    $machineName = 'post_message_to_list';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[messageId]' => $messageId]);
  }

  /**
   * Imports and sends.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/importSend.htm
   */
  public function importAndSendMessage($json) {
    $machineName = 'import_and_send_message';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Returns subscription status for a mobile number or subscriber key.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/contactsSubscriptions.htm
   */
  public function getSubscriptionStatus($json) {
    $machineName = 'get_subscription_status';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Initiates a message to one or more mobile numbers.
   *
   * @param string $messageId
   *   The encoded message ID.
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/postMessageContactSend.htm
   */
  public function postMessageToNumber($messageId, $json) {
    $machineName = 'post_message_to_number';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[message_id]' => $messageId]);
  }

  /**
   * Retrieves the tracking history of a queued MO.
   *
   * @param string $tokenId
   *   Token Id returned for the queued MO.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getQueueMOHistory.htm
   */
  public function getTrackingHistoryOfQueuedMo($tokenId) {
    $machineName = 'get_tracking_history_of_queued_mo';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[tokenId]' => $tokenId]);
  }

  /**
   * Queues a contact import.
   *
   * @param string $listId
   *   The list id.
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/queueContactImport.htm
   */
  public function queueContactImport($listId, $json) {
    $machineName = 'queue_contact_import';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[listId]' => $listId]);
  }

  /**
   * Refreshes a list.
   *
   * @param string $listId
   *   The Id of the list to refresh.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/postRefreshSMSAudience.htm
   */
  public function refreshList($listId) {
    $machineName = 'refresh_list';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[listId]' => $listId]);
  }

  /**
   * Deletes a keyword on an account given a keyword and long code.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/deleteKeywordViaKeywordLongCode.htm
   */
  public function deleteKeywordByLongCode($json) {
    $machineName = 'delete_keyword_by_long_code';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Retrieves the delivery status of a queued MO.
   *
   * @param string $tokenId
   *   Token Id returned for the queued MO.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getQueueMODelivery.htm
   */
  public function getDeliveryStatusOfQueuedMo($tokenId) {
    $machineName = 'get_delivery_status_of_queued_mo';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[tokenId]' => $tokenId]);
  }

  /**
   * Returns status for a message sent to a group of mobile numbers.
   *
   * @param string $messageId
   *   Message Id provided for the messageList.
   * @param string $tokenId
   *   Token Id returned for the messageList.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getMessageList.htm
   */
  public function getMessageListStatus($messageId, $tokenId) {
    $machineName = 'get_message_list_status';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[messageId]' => $messageId, '[tokenId]' => $tokenId]);
  }

  /**
   * Retrieves the status of a ImportSend automation.
   *
   * @param string $tokenId
   *   The ID provided in the ImportSend REST response.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/importSendStatus.htm
   */
  public function getImportSendStatus($tokenId) {
    $machineName = 'get_import_send_status';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[tokenId]' => $tokenId]);
  }

  /**
   * Generates a .csv fiie containing information regarding SMS message delivery for a specific MessageList and places the report in the Enhanced FTP location for the Marketing Cloud account.
   *
   * @param string $tokenId
   *   The ID provided in the MessageList REST response.
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/importSendDeliveryReport.htm
   */
  public function createImportSendDeliveryReport($tokenId, $json) {
    $machineName = 'create_import_send_delivery_report';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[tokenId]' => $tokenId]);
  }

  /**
   * Triggers a delivery report for messageList API.
   *
   * @param string $tokenId
   *   The ID provided in the MessageList REST response.
   * @param string $messageID
   *   The API key of the message definition
   *   (configured in the MobileConnect user interface)
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/messageListDeliveryReport.htm
   */
  public function createMessageListDeliveryReport($tokenId, $messageID, $json) {
    $machineName = 'create_message_list_delivery_report';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[tokenId]' => $tokenId, '[messageID]' => $messageID]);
  }

  /**
   * Retrieves the refresh list status.
   *
   * @param string $listId
   *   The ID of the list found in the MobileConnect interface.
   * @param string $tokenId
   *   The unique ID returned when using the RefreshList operation.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getRefreshSMSAudience.htm
   */
  public function getRefreshListStatus($listId, $tokenId) {
    $machineName = 'get_refresh_list_status';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[listId]' => $listId, '[tokenId]' => $tokenId]);
  }

  /**
   * Retrieves the refresh list status.
   *
   * @param string $listId
   *   The ID of the list found in the MobileConnect interface.
   * @param string $tokenId
   *   The unique ID returned when using the RefreshList operation.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getRefreshSMSAudience.htm
   */
  public function getImportStatus($listId, $tokenId) {
    $machineName = 'get_import_status';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[listId]' => $listId, '[tokenId]' => $tokenId]);
  }

  /**
   * Deletes a keyword on an account.
   *
   * @param array|objec|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/deleteKeywordViaKeywordShortCodeCountryCode.htm
   */
  public function deleteKeywordByShortCode($json) {
    $machineName = 'delete_keyword_by_short_code';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Retrieves the overall delivery status of a message to a contact.
   *
   * @param string $messageId
   *   Message Id provided for the messageContact.
   * @param string $tokenId
   *   Token Id returned for the messageContact.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getMessageContactDelivery.htm
   */
  public function getMessageContactStatus($messageId, $tokenId) {
    $machineName = 'get_message_contact_status';
    return $this->apiCall(
      $this->moduleName,
      $machineName,
      new \stdClass(),
      ['[messageId]' => $messageId, '[tokenId]' => $tokenId]
    );
  }

  /**
   * Retrieves the tracking history of a message to a mobile number.
   *
   * @param string $messageId
   *   Message Id provided for the messageContact.
   * @param string $tokenId
   *   Token Id returned for the messageContact.
   * @param string|int $mobileNumber
   *   Mobile number for the messageContact.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getMessageContactHistory.htm
   */
  public function getMessageContactHistory($messageId, $tokenId, $mobileNumber) {
    $machineName = 'get_message_contact_history';
    return $this->apiCall(
      $this->moduleName,
      $machineName,
      new \stdClass(),
      [
        '[messageId]' => $messageId,
        '[tokenId]' => $tokenId,
        '[mobileNumber]' => $mobileNumber,
      ]
    );
  }

}
