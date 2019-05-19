<?php

namespace Drupal\way2sms\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'custom action' action.
 *
 * @RulesAction(
 *   id = "way2sms_send_sms",
 *   label = @Translation("Send SMS"),
 *   category = @Translation("Way2SMS"),
 *   context = {
 *     "reciever_contact_field" = @ContextDefinition("string",
 *       label = @Translation("Reciever's Contact Number"),
 *       description = @Translation("Enter Reciever's Contact Number.")
 *     ),
 *    "recievers_message" = @ContextDefinition("string",
 *       label = @Translation("Message for reciever"),
 *       description = @Translation("Enter message for reciever.")
 *     ),
 *   }
 * )
 */
class Way2smsSendSms extends RulesActionBase {

  /**
   * Executes the action with the given context.
   *
   * @param string $reciever_contact_field
   *   Reciever's Contact Number.
   * @param string $recievers_message
   *   Message for reciever.
   */
  protected function doExecute($reciever_contact_field, $recievers_message) {
    $sender_number = \Drupal::config('way2sms.settings')->get('way2sms_senders_phone_number');
    $sender_pass = \Drupal::config('way2sms.settings')->get('way2sms_senders_password');
    $result = _way2sms_send(
      $sender_number,
      $sender_pass,
      $reciever_contact_field,
      $recievers_message
    );
    if (empty($result[0]['result'])) {
      \Drupal::logger('way2sms')->error('Error sending message to reciever_contact_field : @num with message : @msg', ['@num' => $reciever_contact_field, '@msg' => $recievers_message]);
    }
  }

}
