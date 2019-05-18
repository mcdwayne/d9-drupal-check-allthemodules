<?php

namespace Drupal\marketing_cloud_messages;

use Drupal\marketing_cloud\MarketingCloudService;

/**
 * Class MessagesService.
 *
 * For all of the API service calls, a correct JSON data payload is expected.
 * This is then validated against the JSON Schema. This approach minimises
 * any short-term issues with changes in the SF API, provides a sanitized
 * interface to send API calls and leaves flexibility for any modules that
 * want to use this as a base-module.
 *
 * @package Drupal\marketing_cloud
 */
class MessagesService extends MarketingCloudService {

  private $moduleName = 'marketing_cloud_messages';

  /**
   * Sends an email to a contact.
   *
   * @param string $triggeredSendDefinitionId
   *   ID of the entry event send definition that comes from the response when
   *   creating a TriggeredSendDefinition. Either this or the external key is
   *   required.
   *   or
   *   External key of the entry event send definition. Either this or the
   *   ObjectID is required.
   * @param array|object|string $json
   *   The payload data for the Json.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/messageDefinitionSends.htm
   */
  public function sendEmail($triggeredSendDefinitionId, $json = NULL) {
    $machineName = 'send_email';
    $json = empty($json) ? new \stdClass() : $json;
    return $this->apiCall($this->moduleName, $machineName, $json, ['[triggeredSendDefinitionId]' => $triggeredSendDefinitionId]);
  }

  /**
   * Gets an email delivery status.
   *
   * @param string $key
   *   The ID of the entry event send definition, included in URL as id:your
   *   ID value here or just the ID. Either this or the external key is required
   *   or
   *   External key of the entry event send definition. Either this or the
   *   ObjectID is required.
   * @param string $recipientSendId
   *   The RecipientSendId value returned from the /messageDefinitionSends
   *   send service, which is the unique identifier for a single email send.
   *
   * @return array|bool|null
   *   Return the API call result or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/messageDefinitionSendsDeliveryRecords.htm
   */
  public function getEmailDeliveryStatus($key, $recipientSendId) {
    $machineName = 'get_email_delivery_status';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[key]' => $key, '[recipientSendId]' => $recipientSendId]);
  }

}
