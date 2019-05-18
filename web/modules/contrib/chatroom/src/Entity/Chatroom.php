<?php


namespace Drupal\chatroom\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the chatroom entity class.
 *
 * @ContentEntityType(
 *   id = "chatroom",
 *   label = @Translation("Chatroom"),
 *   handlers = {
 *     "storage" = "Drupal\chatroom\ChatroomStorage",
 *     "storage_schema" = "Drupal\chatroom\ChatroomStorageSchema",
 *     "view_builder" = "Drupal\chatroom\ChatroomViewBuilder",
 *     "access" = "Drupal\chatroom\ChatroomAccessControlHandler",
 *     "views_data" = "Drupal\chatroom\ChatroomViewsData",
 *     "form" = {
 *       "add" = "Drupal\chatroom\Form\ChatroomForm",
 *       "default" = "Drupal\chatroom\Form\ChatroomForm",
 *       "delete" = "Drupal\chatroom\Form\ChatroomDeleteForm",
 *       "edit" = "Drupal\chatroom\Form\ChatroomForm"
 *     }
 *   },
 *   base_table = "chatroom",
 *   data_table = "chatroom_field_data",
 *   translatable = TRUE,
 *   entity_keys = {
 *     "id" = "cid",
 *     "label" = "title",
 *     "langcode" = "langcode",
 *     "uid" = "uid",
 *     "uuid" = "uuid",
 *   },
 *   render_cache = FALSE,
 *   links = {
 *     "canonical" = "/chatroom/{chatroom}",
 *     "delete-form" = "/chatroom/{chatroom}/delete",
 *     "edit-form" = "/chatroom/{chatroom}/edit",
 *   },
 *   common_reference_target = TRUE,
 *   field_ui_base_route = "entity.chatroom.settings_form"
 * )
 */
class Chatroom extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];
    $fields['cid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Chatroom ID'))
      ->setDescription(t('The chatroom ID.'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The chatroom UUID.'))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The name of the chatroom.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -10,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -10,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The username of the chatroom creator.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\chatroom\Entity\Chatroom::getCurrentUserId')
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('view', TRUE);

    $fields['view_roles'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Roles that can view this chatroom'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDescription(t('The roles that allow users to view this chatroom'))
      ->setSetting('target_type', 'user_role')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['post_roles'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Roles that can post in this chatroom'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDescription(t('The roles that allow users to post in this chatroom.'))
      ->setSetting('target_type', 'user_role')
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language'))
      ->setDescription(t('The chatroom language code.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'hidden',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'language_select',
        'weight' => 2,
      ));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the user was created.'));

    return $fields;
  }

  /**
   * Return the roles that are allowed to view this chatroom.
   *
   * @return array
   *   Array of role machine names.
   */
  public function getViewRoles() {
    $roles = array();

    foreach ($this->get('view_roles') as $role) {
      if ($role->target_id) {
        $roles[] = $role->target_id;
      }
    }

    return $roles;
  }

  /**
   * Return the roles that are allowed to post in this chatroom.
   *
   * @return array
   *   Array of role machine names.
   */
  public function getPostRoles() {
    $roles = array();

    foreach ($this->get('post_roles') as $role) {
      if ($role->target_id) {
        $roles[] = $role->target_id;
      }
    }

    return $roles;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }


  /**
   * Load older messages, in reverse order.
   *
   * @param $cmid
   *   Only load messages with cmids less than this value.
   * @param limit
   *   The number of messages to return.
   * @return
   *   Array of message entities.
   */
  public function loadPreviousMessages($cmid, $limit = 20) {
    return $this->loadMessages(['max_cmid' => $cmid], $limit, 'DESC');
  }

  /**
   * Load the latest messages.
   *
   * @param $limit
   *   Number of messages to load.
   * @return
   *   Array of message entities.
   */
  public function loadLatestMessages($limit = 20) {
    $messages = $this->loadMessages([], $limit, 'DESC');
    $messages = array_reverse($messages);
    return $messages;
  }

  /**
   * Get messages that fulfil the given conditions.
   *
   * @param $conditions
   *   Array of conditions. Possible values:
   *     'min_cmid': Return messages whose cmid is larger than this.
   *     'max_cmid': Return messages whose cmid is smaller than this.
   * @return
   *   Array of message entities.
   */
  public function loadMessages($conditions = [], $limit = FALSE, $order = 'ASC') {
    $query = \Drupal::entityQuery('chatroom_message')
      ->condition('cid', $this->cid->value)
      ->sort('cmid', $order);

    if (!empty($conditions['min_cmid'])) {
      $query->condition('cmid', $conditions['min_cmid'], '>');
    }

    if (!empty($conditions['max_cmid'])) {
      $query->condition('cmid', $conditions['max_cmid'], '<');
    }

    if ($limit) {
      $query->range(0, $limit);
    }

    $cmids = $query->execute();

    $storage = \Drupal::entityManager()->getStorage('chatroom_message');
    $chatroom_messages = $storage->loadMultiple($cmids);

    return $chatroom_messages;
  }

  /**
   * Returns an array of online users.
   *
   * @return array
   *   Array of user entities.
   */
  public function getOnlineUsers() {
    $info = nodejs_get_content_channel_users('chatroom_' . $this->cid->value);
    $users = array();
    if (!empty($info['uids'])) {
      $users = user_load_multiple($info['uids']);
    }
    return $users;
  }

  /**
   * Get the message count for a chat.
   */
  public function getMessageCount() {
    return \Drupal::entityQuery('chatroom_message')
      ->condition('cid', $this->cid->value)
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    // Delete messages belonging to the chatrooms that are being deleted.
    foreach ($entities as $entity) {
      $chatroom_messages = $entity->loadMessages();

      foreach ($chatroom_messages as $message) {
        $message->delete();
      }
    }
  }

}
