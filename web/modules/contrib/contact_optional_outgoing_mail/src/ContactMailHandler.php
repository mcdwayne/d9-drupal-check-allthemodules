<?php

namespace Drupal\contact_optional_outgoing_mail;

use Drupal\contact\MailHandler;
use Drupal\contact\MessageInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Class ContactMailHandler.
 *
 * This is our custom contact.mail_handler service which only calls its parent
 * sendMailMessages method when there are recipients set, so Drupal won't show
 * any errors regarding missing e-mail recipients.
 *
 * @package Drupal\contact_optional_outgoing_mail
 */
class ContactMailHandler extends MailHandler {

  /**
   * {@inheritdoc}
   */
  public function sendMailMessages(MessageInterface $message, AccountInterface $sender) {

    $contact_form = $message->getContactForm();

    if (!$message->isPersonal()) {
      $recipients = $contact_form->getRecipients();

      if (count($recipients) === 0) {
        return;
      }
    }

    parent::sendMailMessages($message, $sender);
  }

}
