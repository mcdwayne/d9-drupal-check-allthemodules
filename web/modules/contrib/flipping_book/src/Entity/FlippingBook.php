<?php

namespace Drupal\flipping_book\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Flipping Book entity.
 *
 * @ingroup flipping_book
 *
 * @ContentEntityType(
 *   id = "flipping_book",
 *   label = @Translation("Flipping Book"),
 *   bundle_label = @Translation("Flipping Book type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\flipping_book\FlippingBookListBuilder",
 *     "views_data" = "Drupal\flipping_book\Entity\FlippingBookViewsData",
 *     "translation" = "Drupal\flipping_book\FlippingBookTranslationHandler",
 *     "form" = {
 *       "default" = "Drupal\flipping_book\Form\FlippingBookForm",
 *       "add" = "Drupal\flipping_book\Form\FlippingBookForm",
 *       "edit" = "Drupal\flipping_book\Form\FlippingBookForm",
 *       "delete" = "Drupal\flipping_book\Form\FlippingBookDeleteForm",
 *     },
 *     "access" = "Drupal\flipping_book\FlippingBookAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\flipping_book\FlippingBookHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "flipping_book",
 *   data_table = "flipping_book_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer flipping book entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *     "directory" = "directory",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/flipping-book/{flipping_book}",
 *     "add-page" = "/admin/content/flipping-book/add",
 *     "add-form" = "/admin/content/flipping-book/add/{flipping_book_type}",
 *     "edit-form" = "/admin/content/flipping-book/{flipping_book}/edit",
 *     "delete-form" = "/admin/content/flipping-book/{flipping_book}/delete",
 *     "collection" = "/admin/content/flipping-book",
 *   },
 *   bundle_entity_type = "flipping_book_type",
 *   field_ui_base_route = "entity.flipping_book_type.edit_form"
 * )
 */
class FlippingBook extends ContentEntityBase implements FlippingBookInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += array(
      'user_id' => \Drupal::currentUser()->id(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * Gets the Flipping Book type label.
   *
   * @return string
   *   The Flipping Book type label.
   */
  public function getTypeLabel() {
    $storage = $this->entityTypeManager()->getStorage('flipping_book_type');
    $flipping_book_type = $storage->load($this->bundle());
    return $flipping_book_type->label();
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
   * {@inheritdoc}
   */
  public function getDirectory() {
    $dir = $this->get('directory')->value;
    if (preg_match('/\.tar$/', $dir)) {
      $dir .= '/' . preg_replace('/\.tar$/', '', $dir);
    }

    return $dir;
  }

  /**
   * {@inheritdoc}
   */
  public function setDirectory($path) {
    $this->set('directory', $path);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Flipping Book entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Flipping Book entity.'))
      ->setSettings(array(
        'max_length' => 50,
        'text_processing' => 0,
      ))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => -4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Flipping Book is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['directory'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Directory'))
      ->setDescription(t('The Flipping Book directory path.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', array(
        'type' => 'hidden',
      ))
      ->setDisplayOptions('form', array(
        'type' => 'hidden',
      ))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['file'] = BaseFieldDefinition::create('file')
      ->setLabel(t('Flipping Book'))
      ->setDescription(t('Use this field to upload your Flipping Book archive.'))
      ->setSettings(array(
        'file_extensions' => 'zip tar gz',
        'file_directory' => 'flipping_books',
      ))
      ->setRequired(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'flipping_book_iframe_formatter',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'label' => 'hidden',
        'type' => 'file_generic',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
