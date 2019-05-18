<?php

namespace Drupal\box\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Box entity.
 *
 * @ingroup box
 *
 * @ContentEntityType(
 *   id = "box",
 *   label = @Translation("Box"),
 *   label_collection = @Translation("Boxes"),
 *   label_singular = @Translation("box"),
 *   label_plural = @Translation("boxes"),
 *   label_count = @PluralTranslation(
 *     singular = "@count box",
 *     plural = "@count boxes"
 *   ),
 *   bundle_label = @Translation("Box type"),
 *   handlers = {
 *     "storage" = "Drupal\box\BoxStorage",
 *     "view_builder" = "Drupal\box\Entity\BoxViewBuilder",
 *     "list_builder" = "Drupal\box\BoxListBuilder",
 *     "views_data" = "Drupal\box\Entity\BoxViewsData",
 *     "translation" = "Drupal\box\BoxTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\box\Form\BoxForm",
 *       "add" = "Drupal\box\Form\BoxForm",
 *       "edit" = "Drupal\box\Form\BoxForm",
 *       "delete" = "Drupal\box\Form\BoxDeleteForm",
 *     },
 *     "access" = "Drupal\box\BoxAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\box\BoxHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "box",
 *   data_table = "box_field_data",
 *   revision_table = "box_revision",
 *   revision_data_table = "box_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer box entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status"
 *   },
 *   links = {
 *     "canonical" = "/box/{box}",
 *     "add-page" = "/box/add",
 *     "add-form" = "/box/add/{box_type}",
 *     "edit-form" = "/box/{box}",
 *     "delete-form" = "/box/{box}/delete",
 *     "version-history" = "/box/{box}/revisions",
 *     "revision" = "/box/{box}/revisions/{box_revision}/view",
 *     "revision_revert" = "/box/{box}/revisions/{box_revision}/revert",
 *     "translation_revert" = "/box/{box}/revisions/{box_revision}/revert/{langcode}",
 *     "revision_delete" = "/box/{box}/revisions/{box_revision}/delete",
 *     "collection" = "/admin/content/box",
 *   },
 *   bundle_entity_type = "box_type",
 *   field_ui_base_route = "entity.box_type.edit_form"
 * )
 */
class Box extends EditorialContentEntityBase implements BoxInterface {

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
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    } elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the box owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
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
   * Gets the machine name.
   *
   * @return string
   *   The machine name of the entity.
   */
  public function machineName() {
    return $this->machine_name->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Box entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine-readable name'))
      ->setDescription(t('Machine-readable name of the box'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->addConstraint('UniqueField', [])
      ->setDisplayOptions('form', [
        'type' => 'machine_name',
        'weight' => -4,
        'settings' => [
          'source' => [
            'title',
            'widget',
            0,
            'value',
          ],
          'exists' => '\Drupal\box\BoxStorage::loadByMachineName',
        ],
      ]);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setTranslatable(TRUE)
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Box is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function bundleLabel() {
    $bundle = BoxType::load($this->bundle());
    return $bundle ? $bundle->label() : FALSE;
  }

}
