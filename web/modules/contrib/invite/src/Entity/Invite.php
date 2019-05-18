<?php

namespace Drupal\invite\Entity;

use Drupal\Core\Config\Config;
use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\invite\InviteInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Invite entity.
 *
 * @ingroup invite
 *
 * @ContentEntityType(
 *   id = "invite",
 *   label = @Translation("Invite"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\invite\InviteListBuilder",
 *     "views_data" = "Drupal\invite\Entity\InviteViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\invite\Form\InviteForm",
 *       "add" = "Drupal\invite\Form\InviteForm",
 *       "edit" = "Drupal\invite\Form\InviteForm",
 *     },
 *     "access" = "Drupal\invite\InviteAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\invite\InviteHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "invite",
 *   admin_permission = "administer invite entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "test" = "test",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/invite/{invite}",
 *     "add-form" = "/admin/structure/invite/add",
 *     "edit-form" = "/admin/structure/invite/{invite}/edit",
 *     "collection" = "/admin/structure/invite",
 *   },
 *   field_ui_base_route = "invite.settings"
 * )
 */
class Invite extends ContentEntityBase implements InviteInterface {
  use EntityChangedTrait;

  /**
   * The plugin creating this invite.
   *
   * @var plugin
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    // Generate unique registration code.
    do {
      $reg_code = user_password(10);
      $result = Database::getConnection()
        ->query('SELECT reg_code FROM {invite} WHERE reg_code = :reg_code', [':reg_code' => $reg_code])
        ->fetchField();

    } while ($result !== FALSE);

    $values += [
      'user_id' => \Drupal::currentUser()->id(),
      'created' => REQUEST_TIME,
      'expires' => REQUEST_TIME + \Drupal::config('invite.invite_config')->get('invite_expiration') * 24 * 60 * 60,
      'invitee' => 0,
      'type' => !empty($values['type']) ? $values['type'] : '',
      'status' => 1,
      'reg_code' => $reg_code,
      'data' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    if (!empty($this->plugin)) {
      $plugin_manager = \Drupal::service('plugin.manager.invite');
      $plugin = $plugin_manager->createInstance($this->plugin);
      $plugin->send($this);
    }
  }

  /**
   * Sets the plugin id.
   */
  public function setPlugin($plugin) {
    $this->plugin = $plugin;
  }

  /**
   * Gets the plugin id.
   */
  public function getPlugin() {
    return $this->plugin;
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
  public function getInvitee() {
    return $this->get('invitee')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setJoined($time) {
    $this->set('joined', $time);
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
  public function setInvitee(UserInterface $account) {
    $this->set('invitee', $account->id());
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
  public function getRegCode() {
    return $this->get('reg_code')->value;
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Invite entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Invite entity.'))
      ->setReadOnly(TRUE);

    $fields['reg_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Registration Code'))
      ->setDescription(t('The invite registration code.'))
      ->setSettings([
        'max_length' => 10,
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

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Invite Type'))
      ->setDescription(t('The invite type.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
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

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Invite entity.'))
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

    $fields['invitee'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Invitee'))
      ->setDescription(t('The user id of the person being invited.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
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

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The Unix timestamp when the invite was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['expires'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expiration'))
      ->setDescription(t('The Unix timestamp when the invite will expire.'));

    $fields['joined'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Joined'))
      ->setDescription(t('Will be filled with the time the invite was accepted upon registration.'));

    $fields['data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Data'))
      ->setDescription(t('Stores axiliary data.'));

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Expiration'))
      ->setDescription(t('Invitation status'))
      ->setSettings([
        'max_length' => 11,
        'text_processing' => 0,
      ]);

    return $fields;
  }

}
