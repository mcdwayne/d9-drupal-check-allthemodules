<?php

namespace Drupal\rokka\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Rokka Metadata entity.
 *
 * @ingroup rokka
 *
 * @ContentEntityType(
 *   id = "rokka_metadata",
 *   label = @Translation("Rokka Metadata"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\rokka\Entity\Controller\RokkaMetadataListBuilder",
 *     "views_data" = "Drupal\rokka\Entity\RokkaMetadataViewsData",
 *     "storage_schema" = "Drupal\rokka\Entity\MetadataStorageSchema",
 *     "form" = {
 *       "default" = "Drupal\rokka\Form\RokkaMetadataForm",
 *       "add" = "Drupal\rokka\Form\RokkaMetadataForm",
 *       "edit" = "Drupal\rokka\Form\RokkaMetadataForm",
 *       "delete" = "Drupal\rokka\Form\RokkaMetadataDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\rokka\RokkaMetadataHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "rokka_metadata",
 *   admin_permission = "administer rokka",
 *   entity_keys = {
 *     "id" = "id",
 *     "hash" = "hash",
 *     "binary_hash" = "binary_hash",
 *     "uri" = "uri",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/rokka_metadata/{rokka_metadata}",
 *     "edit-form" = "/admin/structure/rokka_metadata/{rokka_metadata}/edit",
 *     "collection" = "/admin/structure/rokka_metadata",
 *   },
 *   field_ui_base_route = "rokka_metadata.settings",
 *
 * )
 */
class RokkaMetadata extends ContentEntityBase implements RokkaMetadataInterface {

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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Rokka Metadata entity.'))
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(FALSE)
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

    $fields['hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setDescription(t('The Rokka.io hash (SHA1, 40 chars) of the file.'))
      ->setSettings([
        'max_length' => 40,
        'not null' => TRUE,
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

    $fields['binary_hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setDescription(t('The Rokka.io binary hash of the file.'))
      ->setSettings([
        'max_length' => 40,
        'not null' => TRUE,
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

    $fields['uri'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Uri'))
      ->setDescription(t('The original file URI.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'not null' => TRUE,
      ]);

    $fields['filesize'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('File size'))
      ->setDescription(t('The original file size.'))
      ->setSettings([
        'size' => 'big',
        'not null' => TRUE,
        'unsigned' => TRUE,
      ]);

    $fields['height'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Height'))
      ->setDescription(t('The height of the image.'))
      ->setSettings([
        'size' => 'big',
        'not null' => TRUE,
        'unsigned' => TRUE,
      ]);

    $fields['width'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Width'))
      ->setDescription(t('The width of the image.'))
      ->setSettings([
        'size' => 'big',
        'not null' => TRUE,
        'unsigned' => TRUE,
      ]);

    $fields['format'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Image Format'))
      ->setDescription(t('The image format type of the file.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'not null' => TRUE,
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Rokka Metadata is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getHash() {
    return $this->get('hash')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHash($hash) {
    $this->set('hash', $hash);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBinaryHash() {
    return $this->get('binary_hash')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBinaryHash($binary_hash) {
    $this->set('binary_hash', $binary_hash);
    return $this;
  }


  /**
   * {@inheritdoc}
   */
  public function getFilesize() {
    return $this->get('filesize')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFilesize($filesize) {
    $this->set('filesize', $filesize);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUri() {
    return $this->get('uri')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setUri($uri) {
    $this->set('uri', $uri);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getHeight() {
    return $this->get('height')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setHeight($height) {
    $this->set('height', $height);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getWidth() {
    return $this->get('width')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setWidth($width) {
    $this->set('width', $width);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormat() {
    return $this->get('format')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFormat($format) {
    $this->set('format', $format);
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
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }


}
