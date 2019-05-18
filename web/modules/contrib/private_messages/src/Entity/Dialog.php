<?php

namespace Drupal\private_messages\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Dialog entity.
 *
 * @ingroup private_messages
 *
 * @ContentEntityType(
 *   id = "dialog",
 *   label = @Translation("Dialog"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\private_messages\DialogListBuilder",
 *     "views_data" = "Drupal\private_messages\Entity\DialogViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\private_messages\Form\Dialog\DialogForm",
 *       "add" = "Drupal\private_messages\Form\Dialog\DialogForm",
 *       "edit" = "Drupal\private_messages\Form\Dialog\DialogForm",
 *       "delete" = "Drupal\private_messages\Form\Dialog\DialogDeleteForm",
 *     },
 *
 *     "access" = "Drupal\private_messages\DialogAccessControlHandler",
 *
 *     "route_provider" = {
 *       "html" = "Drupal\private_messages\DialogRouteProvider",
 *     },
 *   },
 *   base_table = "dialogs",
 *   admin_permission = "administer private messages",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/dialog/{dialog}",
 *     "new" = "/dialog/new",
 *     "add" = "/dialog/recipient/{user}",
 *     "edit-form" = "/dialog/{dialog}/edit",
 *     "delete-form" = "/dialog/{dialog}/delete",
 *     "collection" = "/admin/structure/dialog",
 *     "inbox" = "/user/{user}/dialog"
 *   },
 *   field_ui_base_route = "dialog.settings"
 * )
 */
class Dialog extends ContentEntityBase implements DialogInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(
    EntityStorageInterface $storage_controller,
    array &$values
  ) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid'       => \Drupal::currentUser()->id(),
      'recipient' => \Drupal::request()->get('recipient')->id(),
    ];
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
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipient(): UserInterface {
    return $this->get('recipient')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipientId(): int {
    return $this->get('recipient')->target_id;
  }

  /**
   * {@inheritdoc}
   *
   * @TODO: Refactor this.
   *
   * There's not a good place for request checking.
   */
  public function getParticipant(): UserInterface {
    if ($this->getOwnerId() == \Drupal::currentUser()->id()) {
      return $this->getRecipient();
    }
    return $this->getOwner();
  }

  /**
   * {@inheritdoc}
   */
  public function getParticipantId(): int {
    return $this->getParticipant()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type
  ) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Dialog entity.'))
      ->setSettings([
        'target_type' => 'user',
        'handler'     => 'default',
      ])
      ->setDisplayOptions('view', [
        'label'  => 'hidden',
        'type'   => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['recipient'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Dialog with'))
      ->setDescription(t('The user ID with whom dialog takes place.'))
      ->setSettings([
        'target_type' => 'user',
        'handler'     => 'default',
      ])
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label'  => 'hidden',
        'type'   => 'author',
        'weight' => 0,
      ]);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(TRUE)
      ->setDescription(t('Dialog title'))
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label'  => 'hidden',
        'type'   => 'link',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type'   => 'string_textfield',
        'weight' => -10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setSettings([
        'size' => 'tiny',
      ])
      ->setDefaultValue(0)
      ->addConstraint('Range', ['min' => 0, 'max' => 100])
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Dialog is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['messages_count'] = BaseFieldDefinition::create('integer')
      ->setSetting('size', 'tiny')
      ->setLabel(t('Messages count in dialog'))
      ->setDefaultValue(0);

    $fields['recipient_new_count'] = BaseFieldDefinition::create('integer')
      ->setSetting('size', 'tiny')
      ->setLabel('Number of new messages that recipient not seen yet.')
      ->setDefaultValue(0);

    $fields['uid_new_count'] = BaseFieldDefinition::create('integer')
      ->setSetting('size', 'tiny')
      ->setLabel('Number of new messages that author not seen yet.')
      ->setDefaultValue(0);

    return $fields;
  }

  /**
   * Fires when new Message created on current Dialog.
   */
  public function onMessageCreated() {
    $user = \Drupal::currentUser();

    $this->setMessageCount();
    $this->setNewMessagesCount($user);
    $this->save();
  }

  /**
   * Increments messages count attributes.
   *
   * @return $this
   */
  private function setMessageCount() {
    $count = $this->get('messages_count')->value += 1;
    $this->set('messages_count', $count);
    return $this;
  }

  /**
   * Increments corresponding new messages count attribute.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $user
   *
   * @return $this
   */
  private function setNewMessagesCount(AccountProxyInterface $user) {
    $count_attribute = 'recipient_new_count';

    if ($user->id() == $this->getRecipientId()) {
      $count_attribute = 'uid_new_count';
    }

    $count = $this->get($count_attribute)->value += 1;
    $this->set($count_attribute, $count);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getNewMessagesCount(): int {
    $user = \Drupal::currentUser();

    $count_attribute = 'uid_new_count';
    if ($user->id() == $this->getRecipientId()) {
      $count_attribute = 'recipient_new_count';
    }

    return $this->get($count_attribute)->value ?? 0;
  }

}
