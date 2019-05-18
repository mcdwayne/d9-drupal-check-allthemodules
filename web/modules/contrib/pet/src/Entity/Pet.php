<?php

namespace Drupal\pet\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\pet\PetInterface;

/**
 * Defines pet entity class.
 *
 * @ingroup pet
 *
 * @ContentEntityType(
 *   id = "pet",
 *   label = @Translation("Pet Entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\pet\Controller\PetListBuilder",
 *     "form" = {
 *       "default" = "Drupal\pet\Form\PetForm",
 *       "add" = "Drupal\pet\Form\PetForm",
 *       "edit" = "Drupal\pet\Form\PetForm",
 *       "delete" = "Drupal\pet\Form\PetDeleteForm",
 *     },
 *     "access" = "Drupal\pet\PetAccessControlHandler",
 *   },
 *   base_table = "pets",
 *   admin_persmission = "administer previewable email templates",
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "name" = "name",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/pets/{pet}",
 *     "delete-form" = "/admin/structure/pets/{pet}/delete",
 *     "edit-form" = "/admin/structure/pets/{pet}/edit",
 *   },
 *   translatable = TRUE,
 * )
 *
 */
class Pet extends ContentEntityBase implements PetInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The internal identifier for any templates.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE)
      ->setDisplayOptions('view', array(
          'weight' => -10,
        )
      );

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Pets entity.'))
      ->setReadOnly(TRUE);

    $fields['module'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Module'))
      ->setDescription(t('The name of the providing module if the entity has been defined in code.'));

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('A short, descriptive title for this email template. It will be used in administrative interfaces, and in page titles and menu items.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -14,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setDescription(t('The exportable status of the entity.'))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'integer',
        'unsigned' => TRUE,
        'weight' => -12,
      ));

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The Pet entity language code.'))
      ->setRevisionable(TRUE);

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setDescription(t('The subject line of the email template. May include tokens of any token type specified below.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -9,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -9,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail_body'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Mail Body'))
      ->setDescription(t('The body of the email template. May include tokens of any token type specified below.'))
      ->setDefaultValue(NULL)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => -8,
        'settings' => array(
          'rows' => 4,
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['mail_body_plain'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Mail Body Plain'))
      ->setDescription(t('The plain text body of the email template. May include tokens of any token type specified below. If left empty Mime Mail will use drupal_html_to_text() to create a plain text version of the email.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'string_textarea',
        'weight' => -7,
        'settings' => array(
          'rows' => 4,
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['send_plain'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Send only plain text'))
      ->setDescription(t('Send email as plain text only. If checked, only the plain text here will be sent. If unchecked both will be sent as multipart mime.Send email as plain text only. If checked, only the plain text here will be sent. If unchecked both will be sent as multipart mime..'))
      ->setDisplayOptions('form', array(
        'weight' => -6,
        'type' => 'boolean_checkbox',
        'settings' => array(
          'display_label' => TRUE,
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['recipient_callback'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Recipient Callback'))
      ->setDescription(t('The name of a function which will be called to retrieve a list of recipients. This function will be called if the query parameter uid=0 is in the URL. It will be called with one argument, the loaded node (if the PET takes one) or NULL if not. This function should return an array of recipients in the form uid|email, as in 136|bob@example.com. If the recipient has no uid, leave it blank but leave the pipe in. Providing the uid allows token substitution for the user.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['from_override'] = BaseFieldDefinition::create('email')
      ->setLabel(t('From Override'))
      ->setDescription(t('Email to override system from address.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'email',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'email',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cc_default'] = BaseFieldDefinition::create('email')
      ->setLabel(t('CC Default'))
      ->setDescription(t('Emails to be copied by default for each mail sent to recipient. Enter emails separated by lines or commas.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'email',
        'weight' => -3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['bcc_default'] = BaseFieldDefinition::create('email')
      ->setLabel(t('BCC Default'))
      ->setDescription(t('Emails to be blind copied by default for each mail sent to recipient. Enter emails separated by lines or commas.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'email',
        'weight' => -2,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Owner field of the pets.
    // Entity reference field, holds the reference to the user object.
    // The view shows the user name field of the user.
    // The form presents a auto complete field for the user name.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Name'))
      ->setDescription(t('The Name of the associated user.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'entity_reference',
        'weight' => -1,
      ))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
    $this->set('title', $title);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('status', $status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject() {
    return $this->get('subject')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubject($subject) {
    $this->set('subject', $subject);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMailbody() {
    return $this->get('mail_body')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMailbody($mail_body) {
    $this->set('mail_body', $mail_body);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMailbodyPlain() {
    return $this->get('mail_body_plain')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMailbodyPlain($mail_body_plain) {
    $this->set('mail_body_plain', $mail_body_plain);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSendPlain() {
    return $this->get('send_plain')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSendPlain($send_plain) {
    $this->set('send_plain', $send_plain);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipientCallback() {
    return $this->get('recipient_callback')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRecipientCallback($recipient_callback) {
    $this->set('recipient_callback', $recipient_callback);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCCDefault() {
    return $this->get('cc_default')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCCDefault($cc_default) {
    $this->set('cc_default', $cc_default);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBCCDefault() {
    return $this->get('bcc_default')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBCCDefault($bcc_default) {
    $this->set('bcc_default', $bcc_default);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFromOverride() {
    return $this->get('from_override')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFromOverride($from_override) {
    $this->set('from_override', $from_override);
    return $this;
  }

}
