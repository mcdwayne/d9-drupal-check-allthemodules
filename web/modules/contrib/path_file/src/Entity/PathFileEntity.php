<?php

namespace Drupal\path_file\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Path file entity entity.
 *
 * @ingroup path_file
 *
 * @ContentEntityType(
 *   id = "path_file_entity",
 *   label = @Translation("Path file entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\path_file\PathFileEntityListBuilder",
 *     "views_data" = "Drupal\path_file\Entity\PathFileEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\path_file\Form\PathFileEntityForm",
 *       "add" = "Drupal\path_file\Form\PathFileEntityForm",
 *       "edit" = "Drupal\path_file\Form\PathFileEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\path_file\PathFileEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\path_file\PathFileEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "path_file_entity",
 *   admin_permission = "administer path file entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/path-file/{path_file_entity}",
 *     "add-form" = "/admin/structure/path_file_entity/add",
 *     "edit-form" = "/admin/structure/path_file_entity/{path_file_entity}/edit",
 *     "delete-form" = "/admin/structure/path_file_entity/{path_file_entity}/delete",
 *     "collection" = "/admin/structure/path_file_entity",
 *   }
 * )
 */
class PathFileEntity extends ContentEntityBase implements PathFileEntityInterface {

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
  public function getFid() {
    return $this->get('fid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $config = \Drupal::config('path_file.settings');

    // Allows user's to name this Path File.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('A name for this Path File.'))
      ->setSettings(
        array(
          'max_length' => 50,
          'text_processing' => 0,
        )
    )
      ->setDefaultValue('')
      ->setDisplayOptions(
        'view', array(
          'label' => 'above',
          'type' => 'string',
          'weight' => -4,
        )
    )
      ->setDisplayOptions(
        'form', array(
          'type' => 'string_textfield',
          'weight' => -4,
        )
    )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // URL alias for the file.
    $fields['path'] = BaseFieldDefinition::create('path')
      ->setLabel(t('URL alias'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions(
        'form', array(
          'type' => 'path',
          'weight' => 30,
        )
    )
      ->setDisplayConfigurable('form', TRUE)
      ->setCustomStorage(TRUE);

    // Allow user to specify the allowed files.
    $extensions_from_config = $config->get('allowed_extensions');
    // File Upload field.
    $fields['fid'] = BaseFieldDefinition::create('file')
      ->setLabel(t('File Name'))
      ->setDescription(t('The File of the associated event.'))
      ->setSetting('file_extensions', $extensions_from_config)
      ->setDisplayOptions(
        'view', array(
          'label' => 'above',
          'type' => 'file',
          'weight' => -3,
        )
    )
      ->setDisplayOptions(
        'form', array(
          'weight' => -3,
        )
    )
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Can be published or unpublished, defaults to true.
    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Path file entity is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
