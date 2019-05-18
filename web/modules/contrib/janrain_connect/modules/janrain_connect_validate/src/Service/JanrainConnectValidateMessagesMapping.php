<?php

namespace Drupal\janrain_connect_validate\Service;

use Drupal\janrain_connect\Constants\JanrainConnectConstants;
use Drupal\janrain_connect\Constants\JanrainConnectWebServiceConstants;

/**
 * Janrain Connect Validate Class for Mapping Messages.
 */
class JanrainConnectValidateMessagesMapping {

  /**
   * Method to Get Messages Fields.
   *
   * @param array $response
   *   The Janrain Response.
   * @param string $form_id
   *   The Form ID.
   *
   * @return mixed
   *   Return array with the Messages or FALSE.
   */
  public static function getMessagesFields(array $response, $form_id) {
    // Default value.
    $messages = [];

    // Check for invalid fields.
    if (!empty($response['invalid_fields'])) {

      // Get all invalid fields from response.
      $messages = $response['invalid_fields'];

      foreach ($messages as $key => $field_messages) {

        // Check errors by form level.
        if ($key == $form_id) {

          if (is_array($field_messages)) {
            $field_messages = implode($field_messages, ', ');
          }

          $messages[$form_id] = [
            $field_messages,
          ];

          break;
        }
      }

    }

    // If not known errors, display generic error message.
    if (empty($messages) && $response['has_errors']) {
      $messages[] = !empty($response[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_ERROR_DESCRIPTION]) ?
        $response[JanrainConnectWebServiceConstants::JANRAIN_CONNECT_ERROR_DESCRIPTION] : JanrainConnectConstants::JANRAIN_CONNECT_UNKNOWN_ERROR;
    }

    return $messages;
  }

  /**
   * Method to Get Messages.
   *
   * @param string $response
   *   The Janrain Response.
   * @param string $form_id
   *   The Form ID.
   *
   * @return mixed
   *   Return array with the Messages or FALSE.
   */
  public static function getMessages($response, $form_id) {
    // Default value.
    $messages = [];

    // Check for invalid fields.
    if (!empty($response['invalid_fields'])) {

      // Get all invalid fields from response.
      $invalid_fields = $response['invalid_fields'];

      foreach ($invalid_fields as $key => $field_messages) {

        // Check errors by form level.
        if ($key == $form_id) {

          if (count($field_messages) > 1) {
            $field_messages = implode($field_messages, ', ');
          }

          $messages[] = $field_messages;

          break;
        }

        // Check by fields.
        foreach ($field_messages as $field_message) {
          $messages[] = $field_message;
        }
      }
    }

    // If not known errors, display generic error message.
    if (empty($messages) && $response['has_errors']) {
      $messages[] = JanrainConnectConstants::JANRAIN_CONNECT_UNKNOWN_ERROR;
    }

    return $messages;
  }

}
