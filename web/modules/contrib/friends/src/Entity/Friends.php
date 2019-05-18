<?php

namespace Drupal\friends\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;
use Drupal\Core\Url;

/**
 * Defines the Friends entity.
 *
 * @ingroup friends
 *
 * @ContentEntityType(
 *   id = "friends",
 *   label = @Translation("Friends"),
 *   handlers = {
 *     "storage" = "Drupal\friends\FriendsStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\friends\FriendsListBuilder",
 *     "views_data" = "Drupal\friends\Entity\FriendsViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\friends\Form\FriendsForm",
 *       "add" = "Drupal\friends\Form\FriendsForm",
 *       "edit" = "Drupal\friends\Form\FriendsForm",
 *       "delete" = "Drupal\friends\Form\FriendsDeleteForm",
 *     },
 *
 *     "access" = "Drupal\friends\FriendsAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\friends\FriendsHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "friends",
 *   data_table = "friends_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer friends entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *     "recipient" = "recipient",
 *     "updater" = "updater"
 *   },
 *   links = {
 *   "canonical" = "/admin/structure/friends/{friends}",
 *   "add-form" = "/admin/structure/friends/add",
 *   "edit-form" = "/admin/structure/friends/{friends}/edit",
 *   "delete-form" = "/admin/structure/friends/{friends}/delete",
 *   "collection" = "/admin/structure/friends",
 *   },
 *   field_ui_base_route = "friends.settings"
 * )
 */
class Friends extends ContentEntityBase implements FriendsInterface {
  use EntityChangedTrait;

  /**
   * {@inheritdoc}
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
    $this->set('status', $published ? NODE_PUBLISHED : NODE_NOT_PUBLISHED);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Friends entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Friends entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Friends entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Friends is published.'))
      ->setDefaultValue(TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code for the Friends entity.'))
      ->setDisplayOptions('form', [
        'type' => 'language_select',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['recipient'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Friends Request Recipient'))
      ->setDescription(t('The user ID of the user that recieves the Friends Request.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['updater'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Last Updated by'))
      ->setDescription(t('The user ID of the user that last updated the Friends entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId');

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    $current_user = \Drupal::currentUser();
    $this->set('updater', $current_user->id());
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipient() {
    return $this->get('recipient')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getRecipientId() {
    return $this->get('recipient')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdater() {
    return $this->get('updater')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdaterId() {
    return $this->get('updater')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus(bool $human_readable = FALSE) {
    if ($human_readable) {
      $allowed_values = $this->getFieldDefinition('friends_status')->getSetting('allowed_values');
      $value = $allowed_values[$this->get('friends_status')->value];
    }
    else {
      $value = $this->get('friends_status')->value;
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getType(bool $human_readable = FALSE) {
    if ($human_readable) {
      $allowed_values = $this->getFieldDefinition('friends_type')->getSetting('allowed_values');
      $value = $allowed_values[$this->get('friends_type')->value];
    }
    else {
      $value = $this->get('friends_type')->value;
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatusUrl(string $status, array $options = []) {
    $options['attributes']['class'][] = 'friends-api-response--' . $this->id();
    return Url::fromRoute('friends.friends_api_controller_request_response', [
      'friends' => $this->id(),
      'status' => $status,
    ], $options);
  }

}
