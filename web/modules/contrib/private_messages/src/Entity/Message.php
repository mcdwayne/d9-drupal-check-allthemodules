<?php

namespace Drupal\private_messages\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Constants for message's status.
 */
define('SENDED', 1);
define('READ', 20);

/**
 * Defines the Message entity.
 *
 * @ingroup private_messages
 *
 * @ContentEntityType(
 *   id = "message",
 *   label = @Translation("Message"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\private_messages\MessageListBuilder",
 *     "views_data" = "Drupal\private_messages\Entity\MessageViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\private_messages\Form\Message\MessageForm",
 *       "add" = "Drupal\private_messages\Form\Message\MessageForm",
 *       "edit" = "Drupal\private_messages\Form\MessageForm",
 *       "delete" = "Drupal\private_messages\Form\MessageDeleteForm",
 *     },
 *     "access" = "Drupal\private_messages\MessageAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\private_messages\MessageHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "messages",
 *   admin_permission = "administer message entities",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/messages/message/{message}",
 *     "edit-form" = "/message/{message}/edit",
 *     "delete-form" = "/message/{message}/delete",
 *     "collection" = "/admin/structure/messages",
 *   },
 *   field_ui_base_route = "message.settings"
 * )
 */
class Message extends ContentEntityBase implements MessageInterface
{
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
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime()
  {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp)
  {
    $this->set('changed', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime()
  {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp)
  {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner()
  {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId()
  {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid)
  {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account)
  {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished()
  {
    return (bool)$this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published)
  {
    $this->set('status', $published ? true : false);
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public function getDialog()
  {
    return $this->get('dialog_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
  {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['dialog_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Dialog reference'))
      ->setSettings([
        'target_type' => 'dialog',
        'handler' => 'default'
      ])
      ->setStorageRequired(true)
      ->setRequired(true);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Dialog entity.'))
      ->setSettings([
        'target_type' => 'user',
        'handler' => 'default'
      ])
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['message'] = BaseFieldDefinition::create('text_long')
      ->setStorageRequired(true)
      ->setRequired(true)
      ->setDisplayOptions('form', [
        'type'     => 'string_textarea',
        'weight'   => 25,
        'settings' => [
          'rows' => 4,
        ],
      ])
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Message status'))
      ->setDescription(t('The field contains message status (sended, read)'))
      ->setDefaultValue(SENDED);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));


    return $fields;
  }

  /**
   * Gets the Message name.
   *
   * @return string
   *   Name of the Message.
   */
  public function getSubject()
  {
    return 'Message:' . $this->id();
    // TODO: Implement getSubject() method.
  }

  /**
   * Sets the Message name.
   *
   * @param string $name
   *   The Message name.
   *
   * @return \Drupal\private_messages\Entity\MessageInterface
   *   The called Message entity.
   */
  public function setSubject($subject)
  {
    // STUB.
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = true)
  {
    parent::postSave($storage, $update);
    /** @var \Drupal\private_messages\Entity\DialogInterface $dialog */
    $dialog = $this->getDialog();
    $dialog->onMessageCreated();

    // Notify
//    if($this->getDialog()->get('field_pm_notify')->value) {
//      $this->mailer->mail(
//        'private_messages',
//        'new_message',
//        $this->recipient->getEmail(),
//        \Drupal::languageManager()->getCurrentLanguage(),
//        $this
//      );
//    }
  }
}
