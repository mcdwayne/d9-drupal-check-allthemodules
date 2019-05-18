<?php

namespace Drupal\permanent_entities\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\user\UserInterface;

/**
 * Defines the Permanent Entity entity.
 *
 * @ingroup permanent_entities
 *
 * @ContentEntityType(
 *   id = "permanent_entity",
 *   label = @Translation("Permanent Entity"),
 *   label_collection = @Translation("Permanent Entities"),
 *   label_singular = @Translation("permanent entity"),
 *   label_plural = @Translation("permanent entities"),
 *   label_count = @PluralTranslation(
 *     singular = "@count permanent entities",
 *     plural = "@count permanent entities",
 *   ),
 *   bundle_label = @Translation("Permanent Entity Type"),
 *   handlers = {
 *     "storage" = "Drupal\permanent_entities\PermanentEntityStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\permanent_entities\PermanentEntityListBuilder",
 *     "views_data" = "Drupal\permanent_entities\Entity\PermanentEntityViewsData",
 *     "translation" = "Drupal\permanent_entities\PermanentEntityTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\permanent_entities\Form\PermanentEntityForm",
 *       "edit" = "Drupal\permanent_entities\Form\PermanentEntityForm",
 *     },
 *     "access" = "Drupal\permanent_entities\PermanentEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\permanent_entities\PermanentEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "permanent_entity",
 *   data_table = "permanent_entity_field_data",
 *   revision_table = "permanent_entity_revision",
 *   revision_data_table = "permanent_entity_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer permanent entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_user",
 *     "revision_created" = "revision_created",
 *     "revision_log_message" = "revision_log_message",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/permanent_entities/{permanent_entity}",
 *     "edit-form" = "/admin/content/permanent_entities/{permanent_entity}/edit",
 *     "version-history" = "/admin/content/permanent_entities/{permanent_entity}/revisions",
 *     "revision" = "/admin/content/permanent_entities/{permanent_entity}/revisions/{permanent_entity_revision}/view",
 *     "revision_revert" = "/admin/content/permanent_entities/{permanent_entity}/revisions/{permanent_entity_revision}/revert",
 *     "revision_delete" = "/admin/content/permanent_entities/{permanent_entity}/revisions/{permanent_entity_revision}/delete",
 *     "translation_revert" = "/admin/content/permanent_entities/{permanent_entity}/revisions/{permanent_entity_revision}/revert/{langcode}",
 *     "collection" = "/admin/content/permanent_entities",
 *   },
 *   bundle_entity_type = "permanent_entity_type",
 *   field_ui_base_route = "entity.permanent_entity_type.edit_form",
 *   permission_granularity = "bundle"
 * )
 */
class PermanentEntity extends RevisionableContentEntityBase implements PermanentEntityInterface {

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
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    if (!PermanentEntityType::load($this->bundle())) {
      throw new \Exception("Can not create a permanent entity of a non existent type.");
    }

    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make it the anonymous user.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the permanent_entity
    // owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
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

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(new TranslatableMarkup('Permanente Entity ID'))
      ->setDescription(new TranslatableMarkup('The permanente entity ID.'))
      ->setSetting('max_length', 128)
      ->setRequired(TRUE)
      ->addConstraint('UniqueField')
      ->addPropertyConstraints('value', ['Regex' => ['pattern' => '/^[a-z0-9_]+$/']]);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Permanent Entity entity.'))
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

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Permanent Entity entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
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
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Permanent Entity is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

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

}
