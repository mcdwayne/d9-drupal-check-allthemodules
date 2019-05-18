<?php

namespace Drupal\entity_collector\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Entity collection entity.
 *
 * @ingroup entity_collector
 *
 * @ContentEntityType(
 *   id = "entity_collection",
 *   label = @Translation("Entity collection"),
 *   bundle_label = @Translation("Entity collection type"),
 *   handlers = {
 *     "storage" = "Drupal\entity_collector\EntityCollectionStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\entity_collector\Form\EntityCollectionForm",
 *       "add" = "Drupal\entity_collector\Form\EntityCollectionForm",
 *       "edit" = "Drupal\entity_collector\Form\EntityCollectionForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "block" = "Drupal\entity_collector\Form\EntityCollectionForm",
 *     },
 *     "access" = "Drupal\entity_collector\EntityCollectionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\entity_collector\EntityCollectionHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "entity_collection",
 *   revision_table = "entity_collection_revision",
 *   revision_data_table = "entity_collection_field_revision",
 *   admin_permission = "administer entity collection entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "owner",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/collection/{entity_collection}",
 *     "add-page" = "/collection/add",
 *     "add-form" = "/collection/add/{entity_collection_type}",
 *     "edit-form" = "/collection/{entity_collection}/edit",
 *     "delete-form" = "/collection/{entity_collection}/delete",
 *     "version-history" = "/collection/{entity_collection}/revisions",
 *     "revision" = "/collection/{entity_collection}/revisions/{entity_collection_revision}/view",
 *     "revision_revert" = "/collection/{entity_collection}/revisions/{entity_collection_revision}/revert",
 *     "revision_delete" = "/collection/{entity_collection}/revisions/{entity_collection_revision}/delete",
 *     "collection" = "/admin/content/collection",
 *   },
 *   bundle_entity_type = "entity_collection_type",
 *   field_ui_base_route = "entity.entity_collection_type.edit_form"
 * )
 */
class EntityCollection extends RevisionableContentEntityBase implements EntityCollectionInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'owner' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['owner'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user of the one who created the collection and is the power user of the collection.'))
      ->setRevisionable(TRUE)
      ->setSettings([
          'target_type' => 'user',
          'handler',
          'default',
        ]
      )
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Entity collection entity.'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSettings([
        'max_length' => 50,
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

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setTranslatable(FALSE)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'text_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['participants'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Participants'))
      ->setDescription(t(' List of entity reference users that are allowed to edit/contribute to the collection.'))
      ->setRevisionable(TRUE)
      ->setSettings([
          'target_type' => 'user',
          'handler',
          'default',
        ]
      )
      ->setCardinality(-1)
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Entity collection is published.'))
      ->setRevisionable(TRUE)
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
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the entity_collection owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('owner')->target_id;
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
    return $this->get('owner')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('owner', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('owner', $account->id());
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
  public function getParticipantsIds() {
    return array_filter(array_map(function ($value) {
      if (!isset($value['target_id'])) {
        return NULL;
      }
      return $value['target_id'];
    },
      $this->get('participants')->getValue()
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    $cacheTags = parent::getCacheTagsToInvalidate();
    $listCacheTags = \Drupal::service('entity_collection.manager')
      ->getListCacheTags($this->bundle(), $this->getOwnerId());
    return Cache::mergeTags($cacheTags, $listCacheTags);
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

}
