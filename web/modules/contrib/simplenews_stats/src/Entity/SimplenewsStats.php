<?php

namespace Drupal\simplenews_stats\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\simplenews_stats\SimplenewsStatsInterface;
use Drupal\user\UserInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines the simplenews stats entity class.
 *
 * @ContentEntityType(
 *   id = "simplenews_stats",
 *   label = @Translation("Simplenews Stats"),
 *   label_collection = @Translation("Simplenews Stats"),
 *   handlers = {
 *     "storage" = "Drupal\simplenews_stats\SimplenewsStatsEntityStorage",
 *     "view_builder" = "Drupal\simplenews_stats\SimplenewsStatsViewBuilder",
 *     "list_builder" = "Drupal\simplenews_stats\SimplenewsStatsListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\simplenews_stats\SimplenewsStatsAccessControlHandler",
 *     "form" = {
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *     },
 *   },
 *   base_table = "simplenews_stats",
 *   admin_permission = "administer simplenews stats",
 *   entity_keys = {
 *     "id" = "ssid",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/content/simplenews-stats/{simplenews_stats}",
 *     "delete-form" = "/admin/content/simplenews-stats/{simplenews_stats}/delete",
 *     "collection" = "/admin/content/simplenews-stats"
 *   },
 * )
 */
class SimplenewsStats extends ContentEntityBase implements SimplenewsStatsInterface {

  use StringTranslationTrait;

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
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setTitle($title) {
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
    return 1;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    return $this;
  }

  /**
   * Return the number of views.
   */
  public function getViews() {
    return $this->get('views')->value;
  }

  /**
   * Return the number of clicks.
   */
  public function getClicks() {
    return $this->get('clicks')->value;
  }

  /**
   * Return the number of emails sent.
   */
  public function getTotalMails() {
    return $this->get('total_emails')->value;
  }

  /**
   * Return the Newsletter entity.
   */
  public function getNewsletterEntity() {
    return $this->entityTypeManager()
        ->getStorage($this->get('entity_type')->value)
        ->load($this->get('entity_id')->value);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $newsletter = $this->entityTypeManager()
      ->getStorage($this->get('entity_type')->value)
      ->load($this->get('entity_id')->value);

    return ($newsletter) ? $newsletter->label() : $this->t('Deleted');
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields = parent::baseFieldDefinitions($entity_type);

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
        'type'   => 'hidden',
        'weight' => 16,
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
        'type'   => 'hidden',
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
        'type'   => 'hidden',
        'weight' => 19,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['clicks'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Clicks'))
      ->setDescription(t('Clicks'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type'     => 'integer',
        'settings' => [],
        'weight'   => 19,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label'  => 'above',
        'type'   => 'hidden',
        'weight' => 19,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['views'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Views'))
      ->setDescription(t('Views'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type'     => 'integer',
        'settings' => [],
        'weight'   => 19,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label'  => 'above',
        'type'   => 'hidden',
        'weight' => 19,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['total_emails'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Total of Emails'))
      ->setDescription(t('Total of Emails sent'))
      ->setDefaultValue(0)
      ->setDisplayOptions('form', [
        'type'     => 'integer',
        'settings' => [],
        'weight'   => 19,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label'  => 'above',
        'type'   => 'hidden',
        'weight' => 19,
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
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', ['type' => 'hidden']);

    return $fields;
  }

  /**
   * Add one to views.
   * 
   * @return $this
   */
  public function increaseView() {
    $this->increaseField('views');
    return $this;
  }

  /**
   * Add one to clicks.
   * 
   * @return $this
   */
  public function increaseClick() {
    $this->increaseField('clicks');
    return $this;
  }

  /**
   * Add one to total Mails.
   * 
   * @return $this
   */
  public function increaseTotalMail() {
    $this->increaseField('total_emails');
    return $this;
  }

  /**
   * Add one to the given field.
   * 
   * @param string $field
   *    The field name to inscrease.
   */
  protected function increaseField($field) {
    if (!empty($this->{$field}) && !$this->{$field}->isEmpty()) {
      $this->{$field} = $this->get($field)->value + 1;
    }
    else {
      $this->{$field} = 1;
    }
    return $this;
  }

}
