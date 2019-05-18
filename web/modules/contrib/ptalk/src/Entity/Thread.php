<?php

namespace Drupal\ptalk\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\ptalk\ThreadInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the ptalk_thread entity class.
 *
 * @ContentEntityType(
 *   id = "ptalk_thread",
 *   label = @Translation("Private conversation"),
 *   label_collection = @Translation("Private conversations"),
 *   label_singular = @Translation("private conversation"),
 *   label_plural = @Translation("private conversations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count private conversation",
 *     plural = "@count private conversations"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\ptalk\ThreadStorage",
 *     "access" = "Drupal\ptalk\ThreadAccessControlHandler",
 *     "view_builder" = "Drupal\ptalk\ThreadViewBuilder",
 *     "views_data" = "Drupal\ptalk\ThreadViewsData",
 *     "form" = {
 *       "delete" = "Drupal\ptalk\Form\ThreadDeleteForm",
 *     },
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "ptalk_thread",
 *   admin_permission = "administer private conversation",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "tid",
 *     "uuid" = "uuid",
 *     "label" = "subject",
 *     "uid" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/private/conversation/{ptalk_thread}",
 *     "delete-form" = "/private/conversation/{ptalk_thread}/delete",
 *     "collection" = "/private/conversations"
 *   },
 * )
 */
class Thread extends ContentEntityBase implements ThreadInterface {

  /**
   * {@inheritdoc}
   */
  public function deleteThread($delete) {
    $current_user = \Drupal::currentUser();
    ptalk_thread_change_delete($this, $delete, $current_user);
  }

  /**
   * {@inheritdoc}
   */
  public function markThread($status) {
    $current_user = \Drupal::currentUser();
    ptalk_thread_change_status($this, $status, $current_user);
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    $user = $this->get('uid')->entity;
    return $user;
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
  public function getLastMessageAuthor() {
    $user = $this->get('last_message_uid')->entity;
    return $user;
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
  public function getCreatedTime() {
    if (isset($this->get('created')->value)) {
      return $this->get('created')->value;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

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
      ->setDescription(t('The UUID of the ptalk_thread entity.'))
      ->setReadOnly(TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID of the thread author.'))
      ->setTranslatable(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValue(0)
      ->setReadOnly(TRUE);

    $fields['participants'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Participants'))
      ->setDescription(t('The participants of the thread.'))
      ->setTranslatable(FALSE)
      ->setDefaultValue('');

    $fields['subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setDescription(t('Subject of the thread.'))
      ->setSettings([
        'max_length' => 50,
      ])
      ->setDefaultValue('')
      ->setReadOnly(TRUE);

    $fields['last_message_uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Last message author'))
      ->setDescription(t('The name of the author of the last posted message.'))
      ->setTranslatable(TRUE)
      ->setSetting('target_type', 'user')
      ->setDefaultValue(0)
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the thread was created.'))
      ->setTranslatable(TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the thread was last updated.'))
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function participantOf($account) {
    return in_array($account->id(), $this->getParticipantsIds());
  }

  /**
   * {@inheritdoc}
   */
  public function isDeleted() {
    if ($this->index->deleted) {
      return $this->index->deleted == 0 ? FALSE : TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getParticipantsIds() {
    return explode(',', $this->get('participants')->value);
  }

  /**
   * {@inheritdoc}
   */
  public static function postLoad(EntityStorageInterface $storage, array &$entities) {
    $thread_index = $storage->loadIndex(array_keys($entities));
    if ($thread_index) {
      foreach ($entities as $entity) {
        $entity->index = $thread_index[$entity->id()];
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    foreach ($entities as $entity) {
      $mids = db_select('ptalk_message', 'pm')
        ->fields('pm', ['mid'])
        ->condition('tid', $entity->id())
        ->execute()
        ->fetchCol();

      // Delete all messages of this conversation.
      if ($mids) {
        entity_delete_multiple('ptalk_message', $mids);
      }

      // Delete all participants related to the conversation.
      ptalk_delete_thread_index($entity->id());
    }
  }
}
