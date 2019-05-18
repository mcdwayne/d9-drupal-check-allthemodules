<?php

namespace Drupal\filebrowser\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * Defines the Filebrowser metadata entity entity.
 *
 * @ingroup filebrowser
 *
 * @ContentEntityType(
 *   id = "filebrowser_metadata_entity",
 *   label = @Translation("Filebrowser metadata entity"),
 *   base_table = "filebrowser_metadata_entity",
 *   admin_permission = "administer filebrowser metadata entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "name" = "name",
 *   },
 * )
 */

class FilebrowserMetadataEntity extends ContentEntityBase implements FilebrowserMetadataEntityInterface {

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
   * @inheritDoc
   */
  public function getContent() {
    return $this->get('content')->getValue();
  }

  /**
   * @inheritDoc
   */
  public function setContent($content) {
    $this->get('content')->setValue($content);
  }

  /**
   * @inheritDoc
   */
  public function getFid() {
    return $this->get('fid')->getValue();
  }

  /**
   * @inheritDoc
   */
  public function setFid($fid) {
    $this->get('fid')->setValue($fid);
  }

  /**
   * @inheritDoc
   */
  public function getModule() {
    return $this->get('module')->getValue();
  }

  /**
   * @inheritDoc
   */
  public function setModule($fid) {
    $this->get('fid')->setValue($fid);
  }

  /**
   * @inheritDoc
   */
  public function getTheme() {
    return $this->get('theme')->getValue();
  }

  /**
   * @inheritDoc
   */
  public function setTheme($fid) {
    $this->get('theme')->setValue($fid);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Metadata entity entity.'))
      ->setReadOnly(TRUE);

    $fields['fid'] = BaseFieldDefinition::create('integer')
      ->setRequired(true);

    $fields['nid'] = BaseFieldDefinition::create('integer')
      ->setRequired(true);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine readable name'))
      ->setDescription(t('The machine name of the Filebrowser Metadata entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ]);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The Name of the Filebrowser Metadata entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ]);
    $fields['module'] = BaseFieldDefinition::create('string')
      ->setRequired(true);

    $fields['theme'] = BaseFieldDefinition::create('string');

    $fields['content'] = BaseFieldDefinition::create('string_long');

    return $fields;
  }

}
