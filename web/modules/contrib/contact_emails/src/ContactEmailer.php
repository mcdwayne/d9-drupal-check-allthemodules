<?php

namespace Drupal\contact_emails;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Session\AccountProxy;
use Egulias\EmailValidator\EmailValidator;

/**
 * Class ContactEmailerServiceProvider.
 *
 * @package Drupal\contact_emails
 */
class ContactEmailer {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Mail\MailManagerInterface definition.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Drupal\contact_emails\ContactEmails definition.
   *
   * @var \Drupal\contact_emails\ContactEmails
   */
  protected $contactEmails;

  /**
   * Drupal\Core\Session\AccountProxy definition.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Egulias\EmailValidator\EmailValidator definition.
   *
   * @var \Egulias\EmailValidator\EmailValidator;
   */
  protected $emailValidator;

  /**
   * Contact form machine name.
   *
   * @var string
   */
  protected $contactForm;

  /**
   * Contact message entity.
   *
   * @var \Drupal\contact\MessageInterface
   */
  protected $contactMessage;

  /**
   * Constructor.
   */
  public function __construct(
    MailManagerInterface $plugin_manager_mail,
    ContactEmails $contact_emails,
    AccountProxy $current_user,
    EmailValidator $email_validator
  ) {
    $this->mailManager = $plugin_manager_mail;
    $this->contactEmails = $contact_emails;
    $this->currentUser = $current_user;
    $this->emailValidator = $email_validator;
  }

  /**
   * Set the contact form.
   *
   * @param object $contactMessage
   *   The contact message.
   */
  public function setContactMessage($contactMessage) {
    $this->contactForm = $contactMessage->getContactForm()->id();
    $this->contactMessage = $contactMessage;
  }

  /**
   * Send the emails.
   */
  public function sendEmails() {
    /** @var \Drupal\contact_emails\ContactEmailStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('contact_email');
    $contact_emails = $storage->loadValid($this->contactForm, TRUE);
    if ($contact_emails) {
      foreach ($contact_emails as $email) {
        $module = 'contact_emails';
        $key = 'contact_emails';
        $to = $this->getTo($email);
        $reply_to = $email->getReplyTo($this->contactMessage);

        // Stop here if we don't know who to send to.
        if (!$to) {
          $error = $this->t('Unable to determine who to send the message to for for email id @id', [
            '@id' => $email->id(),
          ]);
          drupal_set_message($error, 'warning', FALSE);
          continue;
        }

        $params['subject'] = $email->getSubject($this->contactMessage);
        $params['format'] = $email->getFormat($this->contactMessage);
        $params['message'] = $email->getBody($this->contactMessage);
        $params['contact_message'] = $this->contactMessage;

        // Final prep and send.
        $langcode = $this->currentUser->getPreferredLangcode();
        $this->mailManager->mail($module, $key, $to, $langcode, $params, $reply_to, TRUE);
      }
    }
  }

  /**
   * Get who to send the email to.
   *
   * @param \Drupal\contact_emails\Entity\ContactEmailInterface $email
   *   The email settings.
   *
   * @return string
   *   The to string to be used by the mail manager.
   */
  protected function getTo($email) {
    $to = $this->removeInvalidEmails($email->getRecipients($this->contactMessage));
    return implode(', ', $to);
  }

  /**
   * Remove invalid emails.
   *
   * @param array $emails
   *   An array of potentially valid emails.
   *
   * @return array
   *   An array of valid emails.
   */
  protected function removeInvalidEmails($emails) {
    $valid_emails = [];
    foreach ($emails as $email) {
      if ($this->emailValidator->isValid($email)) {
        $valid_emails[] = $email;
      }
      else {
        $error = $this->t('The following email does not appear to be valid and was not sent to: @email', [
          '@email' => $email,
        ]);
        drupal_set_message($error, 'warning', FALSE);
      }
    }
    return $valid_emails;
  }
}
