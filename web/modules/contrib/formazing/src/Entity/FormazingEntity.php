<?php

namespace Drupal\formazing\Entity;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Formazing entity entity.
 *
 * @ingroup formazing
 *
 * @ContentEntityType(
 *   id = "formazing_entity",
 *   label = @Translation("Formazing entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\formazing\FormazingEntityListBuilder",
 *     "translation" = "Drupal\formazing\FormazingEntityTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\formazing\Form\FormazingEntityForm",
 *       "add" = "Drupal\formazing\Form\FormazingEntityForm",
 *       "edit" = "Drupal\formazing\Form\FormazingEntityForm",
 *       "delete" = "Drupal\formazing\Form\FormazingEntityDeleteForm",
 *     },
 *     "access" = "Drupal\formazing\FormazingEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\formazing\FormazingEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "formazing_entity",
 *   data_table = "formazing_entity_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer formazing entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/formazing_entity/{formazing_entity}",
 *     "add-form" = "/admin/structure/formazing_entity/add",
 *     "edit-form" = "/admin/structure/formazing_entity/{formazing_entity}/edit",
 *     "delete-form" = "/admin/structure/formazing_entity/{formazing_entity}/delete",
 *     "collection" = "/admin/structure/formazing_entity",
 *   },
 *   field_ui_base_route = "formazing_entity.settings"
 * )
 */
class FormazingEntity extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(
    EntityStorageInterface $storage_controller, array &$values
  ) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('Name of the form.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setDescription(t('Is this form published?'))
      ->setDefaultValue(TRUE);

    $fields['has_recipients'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Send email notifications'))
      ->setDescription(t('Should email notifications be sent to a list of recipients for any submission of this form?'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['recipients'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Recipients'))
      ->setDescription(t('Each form submission will be sent to all the recipients addresses listed below.  Each email address must be separated either with comma (,), semi-column (;), space or new line'))
      ->setSettings([
        'max_length' => 2058,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);

    return $this;
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
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function hasRecipients() {
    // To "have recipients" we need the flag to be on and an actual list of recipients
    $recipients = $this->getRecipients();

    return (bool) $this->get('has_recipients')->value && !empty($recipients);
  }

  /**
   * {@inheritdoc}
   */
  public function setHasRecipients($hasRecipient) {
    $this->set('has_recipients', $hasRecipient);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipients() {
    return $this->formatRecipients($this->get('recipients')->value);
  }

  /**
   * {@inheritdoc}
   *
   * @param string $recipients
   */
  public function setRecipients($recipients) {

    $this->set('recipients', implode(';', $this->formatRecipients($recipients)));

    return $this;
  }

  /**
   * Format a raw list of recipients to something we can use easily
   *
   * @param string $recipients List of recipients
   *
   * @return array|string
   */
  private function formatRecipients($recipients) {
    // Uniformize separators
    // Replace all accepted characters with a common one
    $recipients = preg_replace('/\r\n|\r|\n|,|;|\s/', ";", $recipients);

    // Split the string on the common separator
    $recipients_list = explode(';', $recipients);

    // Trim all entries
    $recipients_list = array_map('trim', $recipients_list);

    // Remove all empty or invalid entries
    $recipients_list = array_filter($recipients_list, function($email) {
      return filter_var($email, FILTER_VALIDATE_EMAIL);
    });

    // Remove duplicates
    $recipients_list = array_unique($recipients_list);

    return $recipients_list;
  }
}
