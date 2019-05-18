<?php

/**
 * @file
 * Contains \Drupal\flexmail\Form\SubscribeForm.
 */

namespace Drupal\flexmail\FlexmailHelper;

use Drupal\Component\Render\MarkupTrait;
use Drupal\flexmail\Config\DrupalConfig;
use Finlet\flexmail\FlexmailAPI\FlexmailAPI;

class FlexmailHelper {

  public static function subscribe($email, $list, $language = '', $success = NULL, $error = NULL) {
    $config = \Drupal::config('flexmail.settings');
    $ln = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $flexmail = new FlexmailAPI(new DrupalConfig($config));

    try {
      $contact = [
        'mailingListId' => $list,
        'emailAddressType' => array(
          'emailAddress' => $email,
          'language' => $language,
        ),
      ];

      if ($config->get($ln . '_optin_enabled')) {
        $contact['optInType'] = [
          'messageId' => $config->get($ln . '_optin_messageId'),
          'subject' => $config->get($ln . '_optin_subject'),
          'senderName' => $config->get($ln . '_optin_senderName'),
          'senderEmail' => $config->get($ln . '_optin_senderEmail'),
          'replyEmail' => $config->get($ln . '_optin_replyEmail'),
        ];
      }
      $response = $flexmail->service('Contact')->create($contact);
    }
    catch (\Exception $e) {
      if ($error) {
        drupal_set_message(t($error, array('@message' => $e->getMessage())), 'error');
      }

      return $e;
    }

    if ($success) {
      drupal_set_message(t($success));
    }

    return $response;
  }

  /**
   * Helper function to retrieve list id (Language depended)
   */
  public static function getListId() {
    $config = \Drupal::config('flexmail.settings');
    return $config->get(\Drupal::languageManager()
        ->getCurrentLanguage()
        ->getId() . '_list_id');
  }

}

?>