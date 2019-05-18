<?php

namespace Drupal\iots_channel\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\iots\Controller\RndPass;

/**
 * Defines the Iots Channel entity.
 *
 * @ingroup iots_channel
 *
 * @ContentEntityType(
 *   id = "iots_channel",
 *   label = @Translation("IOTs Channel"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\iots_channel\IotsChannelListBuilder",
 *     "views_data" = "Drupal\iots_channel\Entity\IotsChannelViewsData",
 *     "storage_schema" = "Drupal\iots_channel\IotsChannelStorageSchema",
 *     "form" = {
 *       "default" = "Drupal\iots_channel\Form\IotsChannelForm",
 *       "add" = "Drupal\iots_channel\Form\IotsChannelForm",
 *       "edit" = "Drupal\iots_channel\Form\IotsChannelForm",
 *       "delete" = "Drupal\iots_channel\Form\IotsChannelDeleteForm",
 *     },
 *     "access" = "Drupal\iots_channel\IotsChannelAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\iots_channel\IotsChannelHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "iots_channel",
 *   admin_permission = "administer iots channel entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/iots/channel/{iots_channel}",
 *     "add-form" = "/iots/channel/add",
 *     "edit-form" = "/iots/channel/{iots_channel}/edit",
 *     "delete-form" = "/iots/channel/{iots_channel}/delete",
 *     "collection" = "/iots/channel",
 *   },
 *   field_ui_base_route = "iots_channel.settings"
 * )
 */
class IotsChannel extends ContentEntityBase implements IotsChannelInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    // Set secret if empty.
    if (!$this->secret->value) {
      $this->set('secret', RndPass::gen(25));
    }
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
    return $this->get('user')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user', $account->id());
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Iots Channel entity.'))
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

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Iots Channel entity.'))
      ->setSettings([
        'max_length' => 255,
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
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Iots Channel is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 10,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['device'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Device'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'iots_device')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', [
        'auto_create' => FALSE,
      ])
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => 'Enter here device title...',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['secret'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Secret'))
      ->setDescription(t('Leave blank to generate new one.'))
      ->setSettings([
        'max_length' => 30,
        'is_ascii' => TRUE,
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

    $fields['period'] = BaseFieldDefinition::create('list_integer')
      ->setLabel(t('Period'))
      ->setDefaultValue(0)
      ->setSettings([
        'allowed_values' => [
          0 => t('second'),
          1 => t('minute'),
          2 => t('3 minute'),
          3 => t('15 minute'),
          4 => t('hour'),
          5 => t('week'),
          6 => t('month'),
          7 => t('quarter'),
          8 => t('year'),
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
