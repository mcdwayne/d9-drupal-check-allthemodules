<?php

namespace Drupal\simplenews_stats\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\simplenews_stats\SimplenewsStatsItemInterface;
use Drupal\user\UserInterface;

/**
 * Defines the simplenews stats entity class.
 *
 * @ContentEntityType(
 *   id = "simplenews_stats_item",
 *   label = @Translation("Simplenews Stats Item"),
 *   label_collection = @Translation("Simplenews Stats item"),
 *   handlers = {
 *     "view_builder" = "Drupal\simplenews_stats\SimplenewsStatsItemViewBuilder",
 *     "list_builder" = "Drupal\simplenews_stats\SimplenewsStatsItemListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *   },
 *   base_table = "simplenews_stats_item",
 *   admin_permission = "administer simplenews stats",
 *   entity_keys = {
 *     "id" = "ssiid",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "delete-form" = "/admin/content/simplenews-stats-item/{simplenews_stats_item}/delete",
 *     "collection" = "/admin/content/simplenews-stats-item"
 *   },
 * )
 */
class SimplenewsStatsItem extends ContentEntityBase implements SimplenewsStatsItemInterface {

  /**
   * {@inheritdoc}
   *
   * When a new simplenews stats entity is created, set the uid entity reference
   * to the current user as the creator of the entity.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += ['uid' => \Drupal::currentUser()->id()];
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
  public function isEnabled() {
    return (bool) $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    $this->set('promote', $status);
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the simplenews stats entity.'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type'   => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label'  => 'hidden',
        'type'   => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Author'))
      ->setDescription(t('The user ID of the simplenews stats author.'))
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('form', [
        'type'     => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size'           => 60,
          'placeholder'    => '',
        ],
        'weight'   => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label'  => 'above',
        'type'   => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['snid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Simplenews subscriber ID'))
      ->setDescription(t('Simplenews subscriber Id'))
      ->setDisplayOptions('form', [
        'type'     => 'integer',
        'settings' => [],
        'weight'   => 16,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label'  => 'above',
        'type'   => 'default',
        'weight' => 16,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('Email'))
      ->setDisplayOptions('form', [
        'type'     => 'email',
        'settings' => [],
        'weight'   => 17,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label'  => 'above',
        'type'   => 'email',
        'weight' => 17,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Type'))
      ->setSettings(['max_lenght' => 64])
      ->setDescription(t('Entity Type'))
      ->setDisplayOptions('form', [
        'type'     => 'string',
        'settings' => [],
        'weight'   => 18,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label'  => 'above',
        'type'   => 'string',
        'weight' => 18,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('Entity ID'))
      ->setDisplayOptions('form', [
        'type'     => 'integer',
        'settings' => [],
        'weight'   => 19,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label'  => 'above',
        'type'   => 'integer',
        'weight' => 19,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['route_path'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Path'))
      ->setDescription(t('Path'))
      ->setDisplayOptions('form', [
        'type'     => 'string',
        'settings' => [],
        'weight'   => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label'  => 'above',
        'type'   => 'string',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the simplenews stats was created.'))
      ->setDisplayOptions('view', [
        'label'  => 'above',
        'type'   => 'timestamp',
        'weight' => 21,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type'   => 'datetime_timestamp',
        'weight' => 21,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
