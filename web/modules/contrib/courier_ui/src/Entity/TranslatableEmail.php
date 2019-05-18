<?php

namespace Drupal\courier_ui\Entity;

use Drupal\courier\Entity\Email;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\courier\Exception\ChannelFailure;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines storage for a translatable email.
 *
 * @ContentEntityType(
 *   id = "courier_translatable_email",
 *   label = @Translation("Translatable email"),
 *   handlers = {
 *     "form" = {
 *       "default" = "Drupal\courier\Form\EmailForm",
 *       "add" = "Drupal\courier\Form\EmailForm",
 *       "edit" = "Drupal\courier\Form\EmailForm",
 *       "delete" = "Drupal\courier\Form\EmailDeleteForm",
 *     },
 *     "translation" = "Drupal\courier_ui\TranslatableMailTranslationHandler",
 *   },
 *   admin_permission = "administer courier_email",
 *   base_table = "courier_translatable_email",
 *   data_table = "courier_translatable_email_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "subject",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/courier/translatable-email/{courier_translatable_email}/edit",
 *     "edit-form" = "/courier/translatable-email/{courier_translatable_email}/edit",
 *     "delete-form" = "/courier/translatable-email/{courier_translatable_email}/delete",
 *   }
 * )
 */
class TranslatableEmail extends Email {

  /**
   * Helper function to set receipient user language.
   *
   * @param string $langcode
   *   Language ID.
   */
  public function setReceipientLanguage($langcode) {
    $this->set('message_language', ['value' => $langcode]);
    return $this;
  }

  /**
   * Helper function to get receipient user language.
   *
   * @return string
   *   Language ID.
   */
  public function getReceipientLanguage() {
    return $this->get('message_language')->value;
  }

  /**
   * {@inheritdoc}
   *
   * @param array $messages
   *   The messages to send.
   * @param array $options
   *   Miscellaneous options.
   *   - reply_to: reply-to email address, or leave unset to use site default.
   */
  public static function sendMessages(array $messages, array $options = []) {
    /* @var \Drupal\courier\TranslatableEmailInterface[] $messages */
    foreach ($messages as $message) {
      if ($message->isTranslatable()) {
        $message = $message->getTranslation($message->getReceipientLanguage());
      }
      if (!$email = $message->getEmailAddress()) {
        throw new ChannelFailure('Missing email address for email.');
      }
      $name = $message->getRecipientName();
      $email_to = !empty($name) ? "$name <$email>" : $email;

      $params = [
        'context' => [
          'subject' => $message->getSubject(),
          'message' => $message->getBody(),
        ],
      ];

      /** @var \Drupal\Core\Mail\MailManagerInterface $mailman */
      $mailman = \Drupal::service('plugin.manager.mail');
      $mailman->mail(
        'system',
        'courier_email',
        $email_to,
        $message->language()->getId(),
        $params,
        array_key_exists('reply_to', $options) ? $options['reply_to'] : NULL
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['subject']->setTranslatable(TRUE);
    $fields['body']->setTranslatable(TRUE);

    $fields['message_language'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Message language'))
      ->setDescription(t('The language of the message being sent.'))
      ->setDefaultValue('');

    return $fields;
  }

}
