<?php

namespace Drupal\ptalk\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\ptalk\MessageInterface;
use Drupal\user\UserInterface;

/**
 * Defines the ptalk_message entity class.
 *
 * @ContentEntityType(
 *   id = "ptalk_message",
 *   label = @Translation("Private message"),
 *   label_collection = @Translation("Private messages"),
 *   label_singular = @Translation("private message"),
 *   label_plural = @Translation("private messages"),
 *   label_count = @PluralTranslation(
 *     singular = "@count private message",
 *     plural = "@count private messages"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\ptalk\MessageStorage",
 *     "view_builder" = "Drupal\ptalk\MessageViewBuilder",
 *     "views_data" = "Drupal\ptalk\MessageViewsData",
 *     "form" = {
 *       "default" = "Drupal\ptalk\MessageForm",
 *       "delete" = "Drupal\ptalk\Form\MessageDeleteForm",
 *       "restore" = "Drupal\ptalk\Form\MessageRestoreForm",
 *     },
 *     "access" = "Drupal\ptalk\MessageAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "ptalk_message",
 *   admin_permission = "administer private conversation",
 *   fieldable = TRUE,
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "mid",
 *     "uuid" = "uuid",
 *     "label" = "subject",
 *     "uid" = "author",
 *   },
 *   field_ui_base_route = "entity.ptalk_message.admin_form",
 *   links = {
 *     "delete-form" = "/private/message/{ptalk_message}/delete",
 *     "restore-form" = "/private/message/{ptalk_message}/restore",
 *   },
 * )
 */
class Message extends ContentEntityBase implements MessageInterface {

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    $user = $this->get('author')->value;
    return user_load($user);
  }

  /**
   * {inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('author')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('author', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('author', $account->id());
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
  public function getMessageId() {
    return $this->get('mid')->value;
  }

  /**
   * Checks if the participant has read the message.
   *
   * @return bool
   *   TRUE if message is unread, FALSE otherwise.
   */
  public function isUnRead() {
    return in_array($this->index->status, [1, NULL]) ? TRUE : FALSE;
  }

  /**
   * Checks if the participant is owner of the message.
   *
   * @return bool
   *   TRUE if current user is owner, FALSE otherwise.
   */
  public function isCurrentUserOwner() {
    return $this->getOwnerId() == $this->getCurrentUserId();
  }

  /**
   * Checks if the message for the participant is deleted.
   *
   * @return bool
   *   TRUE if message is deleted, FALSE otherwise.
   */
  public function isDeleted() {
    return $this->index->deleted == 0 ? FALSE : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getThread() {
    if ($this->getThreadId() || $this->thread_id) {
      return Thread::load($this->getThreadId() ?: $this->thread_id);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getThreadId() {
    return $this->get('tid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If this is the first message of the thread then create thread.
    if (!isset($this->thread_id)) {
      $thread = Thread::create([
        'uid' => $this->getOwnerId(),
        'participants' => implode(',', array_keys($this->recipients)),
        'last_message_uid' => $this->getOwnerId(),
        'subject' => $this->getSubject(),
      ]);
      $thread->save();
      $this->tid = $thread->id();
      \Drupal::service('ptalk_thread.manager')->createIndex($thread);
    }
    else {
      $thread = $this->getThread();
      $thread->last_message_uid = $this->getOwnerId();
      $participants_ids = $thread->getParticipantsIds();
      $participants_ids = array_diff($participants_ids, [$this->getOwnerId()]);
      array_unshift($participants_ids, $this->getOwnerId());
      $thread->participants = implode(',', $participants_ids);
      $thread->save();
      $this->tid = $this->thread_id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $database = \Drupal::database();
    $transaction = $database->startTransaction();

    try {
      $send_message = $database->insert('ptalk_message_index')
        ->fields([
          'mid',
          'tid',
          'recipient',
          'deleted',
          'status',
        ]);

      // Save the message.
      parent::save();

      // Populate message to all recipients.
      foreach (array_keys($this->recipients) as $recipient) {
        $status = 1;
        if ($recipient == $this->author->value) {
          // Status '0' will indicate that recipient is the author of the message.
          $status = 0;
        }
        $send_message->values([
          'mid' => $this->id(),
          'tid' => $this->tid->value,
          'recipient' => $recipient,
          'status' => $status,
          'deleted' => 0,
        ]);
      }
      $send_message->execute();
      \Drupal::service('ptalk_thread.manager')->increaseCounts($this);
    }
    catch (\Exception $e) {
      $transaction->rollback();
      watchdog_exception($e->getMessage(), $e);

      $send_message = NULL;
    }

    return $send_message;
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    foreach ($entities as $id => $entity) {
      if ($thread = $entity->getThread()) {
        // Count all messages that belongs to the thread.
        $count_query = db_select('ptalk_message', 'pm');
        $count_query->addExpression('COUNT(pm.mid)');
        $count_query->condition('pm.tid', $thread->id());
        $message_count = $count_query
          ->execute()
          ->fetchField();

        // If this is the last message of the thread
        // then delete thread itself,
        // because thread cannot live without messages.
        if ($message_count < 1) {
          entity_delete_multiple('ptalk_thread', [$thread->id()]);
        }

        // Update count of messages only if message is not the last of the thread
        // and thread is not NULL, because if thread is NULL then the thread probably is being deleted,
        // so no need to decrase count of messages because all messages
        // of the thread will be deleted as thread itself.
        if ($message_count > 1) {
          \Drupal::service('ptalk_thread.manager')->updateCounts($entity->getThread());
        }
      }

      // Delete recipients of the message.
      ptalk_delete_message_index($entity->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['mid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('MID'))
      ->setDescription(t('Private Message ID.'))
      ->setSettings([
        'max_length' => 10
      ])
      ->setReadOnly(TRUE);

    $fields['tid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('TID'))
      ->setDescription(t('Thread ID of the private message.'))
      ->setSettings([
        'max_length' => 10
      ])
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the privatemsg entity.'))
      ->setReadOnly(TRUE);

    $fields['author'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Author'))
      ->setDescription(t('UID of the author.'))
      ->setDefaultValueCallback('Drupal\ptalk\Entity\Message::getCurrentUserId')
      ->setSettings([
        'max_length' => 10,
        'text_processing' => 0,
      ]);

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Message subject'))
      ->setDescription(t('Subject text of the message.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      // Set no default value.
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['body'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Message'))
      ->setDescription(t('The body of the message.'))
      ->setSettings([
        'max_length' => 1024,
        'text_processing' => 0,
      ])
      // Set no default value.
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'text',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['has_tokens'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Token browser'))
      ->setDescription(t('Indicates if the message has tokens.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time when message was sent.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    $message_index = $storage->loadIndex(array_keys($entities));
    if ($message_index) {
      foreach ($entities as $entity) {
        $entity->index = $message_index[$entity->id()];
      }
    }
  }

  /**
   * Default value callback for 'author' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return \Drupal::currentUser()->id();
  }

  /**
   * Return current user name.
   *
   * @return string
   *   The name of the current user.
   */
  public static function getCurrentUserName() {
    return \Drupal::currentUser()->getUserName();
  }

}
