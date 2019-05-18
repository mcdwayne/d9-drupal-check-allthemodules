<?php

namespace Drupal\contact_emails\Entity;

use Drupal\contact\MessageInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\filter\Entity\FilterFormat;

/**
 * Defines the Contact Email entity.
 *
 * @ingroup contact_emails
 *
 * @ContentEntityType(
 *   id = "contact_email",
 *   label = @Translation("Contact email"),
 *   admin_permission = "manage contact form emails",
 *   base_table = "contact_email",
 *   data_table = "contact_email_field_data",
 *   translatable = TRUE,
 *   handlers = {
 *     "storage" = "Drupal\contact_emails\ContactEmailStorage",
 *     "list_builder" = "Drupal\contact_emails\ContactEmailListBuilder",
 *     "form" = {
 *       "default" = "Drupal\contact_emails\Form\ContactEmailForm",
 *       "add" = "Drupal\contact_emails\Form\ContactEmailForm",
 *       "edit" = "Drupal\contact_emails\Form\ContactEmailForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *   },
 *   entity_keys = {
 *     "id" = "email_id",
 *     "label" = "subject",
 *     "langcode" = "langcode",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/contact/emails/{contact_email}",
 *     "edit-form" = "/admin/structure/contact/emails/{contact_email}/edit",
 *     "delete-form" = "/admin/structure/contact/emails/{contact_email}/delete"
 *   }
 * )
 */
class ContactEmail extends ContentEntityBase implements ContactEmailInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Contact form.
    $fields['contact_form'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Contact form'))
      ->setDescription(t('The associated contact form entity.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings([
        'target_type' => 'contact_form',
        'handler' => 'default',
        'default_value' => NULL,
      ])
      ->setDisplayOptions('form', [
        'weight' => -15,
      ]);

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setDescription(t('Subject of the email.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue('')
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -10,
        'settings' => [
          'size' => 64,
        ],
      ]);

    $fields['message'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Message'))
      ->setDescription(t('The email message body.'))
      ->setDefaultValue('')
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 9999,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -5,
        'settings' => [
          'rows' => 4,
        ],
      ]);

    $fields['append_message'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Append message'))
      ->setDescription(t('Append the entire message below the body of the email.'))
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 0,
        'settings' => [
          'display_label' => TRUE,
        ],
      ]);

    $fields['recipient_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Recipient type'))
      ->setDescription(t('How to determine the submitter of the form.'))
      ->setDefaultValue('manual')
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 20,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);

    $fields['recipients'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Recipients'))
      ->setDescription(t('Recipients of the email.'))
      ->setDefaultValue('')
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 9999,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'textarea',
        'weight' => 0,
        'settings' => [
          'display_label' => TRUE,
        ],
      ]);

    $fields['recipient_field'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Recipient field'))
      ->setDescription(t('The field to send to if recipient type is field.'))
      ->setDefaultValue('')
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);

    $fields['recipient_reference'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Recipient reference'))
      ->setDescription(t('The field to send to if recipient type is reference.'))
      ->setDefaultValue('')
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);

    $fields['reply_to_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Reply-to type'))
      ->setDescription(t('The type of reply-to.'))
      ->setDefaultValue('default')
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 10,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);

    $fields['reply_to_email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Reply-to email'))
      ->setDescription(t('The field to set the reply-to as if reply-to type is email.'))
      ->setDefaultValue('')
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_email',
        'weight' => 0,
      ]);

    $fields['reply_to_field'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Reply-to field'))
      ->setDescription(t('The field to set the reply-to as if reply-to type is field.'))
      ->setDefaultValue('')
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);

    $fields['reply_to_reference'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Reply-to reference'))
      ->setDescription(t('The field to use if recipient type is reference.'))
      ->setDefaultValue('')
      ->setTranslatable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enabled'))
      ->setDescription(t('Whether or not this email is enabled.'))
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 10,
        'settings' => [
          'display_label' => TRUE,
        ],
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject(MessageInterface $message) {
    $subject = $this->tokenizeString($this->get('subject')->value, $message);

    // Convert any html to plain text.
    $subject = MailFormatHelper::htmlToText($subject);

    // Remove any line breaks as the above method assumes new lines allowed.
    $subject = str_replace("\n", '', $subject);

    return $subject;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody(MessageInterface $message) {
    $body = $this->get('message');
    $format = $this->getFormat($message);

    // Prepare render array based on text format.
    if ($format == 'text/plain; charset=UTF-8; format=flowed; delsp=yes') {
      $build = [
        '#plain_text' => $this->tokenizeString($body->value, $message),
      ];
    }
    else {
      $build['text'] = [
        '#type' => 'processed_text',
        '#format' => $body->format,
        '#text' => $this->tokenizeString($body->value, $message),
      ];
    }

    // Maybe append the entire message.
    if ($this->get('append_message')->value) {

      // Render the contact message using the mail view mode.
      $render_controller = \Drupal::entityTypeManager()
        ->getViewBuilder($message->getEntityTypeId());
      $message_build = $render_controller->view($message, 'mail');

      // Either add to the html text or plan text.
      if (isset($build['text']['#text'])) {
        $build['message'] = $message_build;
        $build['message']['#prefix'] = '<br /><br />';
      }
      else {
        $message_markup = \Drupal::service('renderer')
          ->renderPlain($message_build);
        $build['#plain_text'] .= "\n\n" . $message_markup;
      }
    }

    // Render the body.
    return \Drupal::service('renderer')->renderPlain($build);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormat(MessageInterface $message) {
    $body = $this->get('message');

    // Default to html.
    $format = 'text/html';

    // Get selected format.
    if ($filter_format = FilterFormat::load($body->format)) {

      // If the selected format does not allow html, set the email as plain
      // text.
      $restrictions = $filter_format->getHtmlRestrictions();

      if ($restrictions && !$restrictions['allowed']) {
        $format = 'text/plain; charset=UTF-8; format=flowed; delsp=yes';
      }
    }

    return $format;
  }

  /**
   * Apply tokens to body value.
   *
   * @param string $string
   *   The string value such as the subject or body.
   * @param Drupal\contact\MessageInterface $message
   *   The contact message.
   *
   * @return string
   *   The tokenized value.
   */
  protected function tokenizeString($string, MessageInterface $message) {
    $data = [
      'contact_message' => $message,
    ];
    $options = [
      'clear' => TRUE,
    ];
    return \Drupal::token()->replace($string, $data, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients(MessageInterface $message) {
    $recipients = [];
    $type = $this->get('recipient_type')->value;

    switch ($type) {
      case 'submitter':
        $recipients = $this->getEmailFromSenderMail($message);
        break;

      case 'field':
        $field = $this->get('recipient_field')->value;
        $recipients = $this->getEmailFromField($message, $field);
        break;

      case 'reference':
        $field = $this->get('recipient_reference')->value;
        $recipients = $this->getEmailFromReferencedField($message, $field);
        break;

      case 'default':
        $recipients[] = \Drupal::config('system.site')->get('mail');
        break;

      case 'manual':
      default:
        $recipients = $this->get('recipients')->value;
        $recipients = preg_replace("/\r|\n/", ",", $recipients);
        $recipients = str_replace(';', ',', $recipients);
        $recipients = explode(',', $recipients);
        $recipients = array_map('trim', $recipients);
        break;
    }
    $recipients = (is_string($recipients) ? [$recipients] : $recipients);
    array_filter($recipients);
    return $recipients;
  }

  /**
   * {@inheritdoc}
   */
  public function getReplyTo(MessageInterface $message) {
    $reply_to = NULL;

    $type = $this->get('reply_to_type')->value;

    switch ($type) {
      case 'submitter':
        $reply_to = $this->getEmailFromSenderMail($message);
        break;

      case 'field':
        $field = $this->get('reply_to_field')->value;
        $reply_to = $this->getEmailFromField($message, $field);
        break;

      case 'reference':
        $field = $this->get('reply_to_reference')->value;
        $reply_to = $this->getEmailFromReferencedField($message, $field);
        break;

      case 'manual':
        // Send to the value of an email field.
        if (!$this->get('reply_to_email')->isEmpty()) {
          $reply_to = $this->get('reply_to_email')->value;
        }
        break;

      case 'default':
      default:
        $reply_to = \Drupal::config('system.site')->get('mail');
        break;
    }

    // We may have an array as a referenced field may be repeating. In that
    // case we take the first email.
    if (is_array($reply_to)) {
      array_filter($reply_to);
      return reset($reply_to);
    }
    else {
      return $reply_to;
    }
  }

  /**
   * Get email address from the sender of the contact message.
   *
   * @param Drupal\contact\MessageInterface $message
   *   The contact message.
   *
   * @return array
   *   An array of emails.
   */
  protected function getEmailFromSenderMail(MessageInterface $message) {
    return $message->getSenderMail();
  }

  /**
   * Get email address from a field.
   *
   * @param Drupal\contact\MessageInterface $message
   *   The contact message.
   * @param object $field
   *   The target field on the message.
   *
   * @return array
   *   An array of emails.
   */
  protected function getEmailFromField(MessageInterface $message, $field) {
    $results = [];
    // Send to the value of an email field.
    if ($message->hasField($field)) {
      // Email could potentially be a repeating field.
      $emails = $message->get($field)->getValue();

      if ($emails) {
        foreach ($emails as $email) {
          if ($email['value']) {
            $results[] = $email['value'];
          }
        }
      }
    }
    return $results;
  }

  /**
   * Get email address from a field.
   *
   * @param Drupal\contact\MessageInterface $message
   *   The contact message.
   * @param object $field
   *   The target field on the message.
   *
   * @return array
   *   An array of emails.
   */
  protected function getEmailFromReferencedField(MessageInterface $message, $field) {
    $results = [];

    // Get the reference path, it consists of:
    // [0] contact_message reference field name.
    // [1] handler.
    // [2] bundle.
    // [3] referenced bundle email field name.
    $reference_path = explode('.', $field);
    if (count($reference_path) != 4) {
      // Something is wrong.
      return $results;
    }
    $reference_field_name = $reference_path[0];
    $entity_type = $reference_path[1];
    $email_field_name = $reference_path[3];

    if ($message->hasField($reference_field_name)) {
      // Reference could potentially be a repeating field.
      $referenced_entity_id = $message->get($reference_field_name)->target_id;

      if ($referenced_entity_id > 0) {
        $storage = \Drupal::entityTypeManager()->getStorage($entity_type);
        /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
        $entity = $storage->load($referenced_entity_id);
        if ($emails = $entity->get($email_field_name)->getValue()) {
          foreach ($emails as $email) {
            if ($email['value']) {
              $results[] = $email['value'];
            }
          }
        }
      }
    }
    return $results;
  }

}
