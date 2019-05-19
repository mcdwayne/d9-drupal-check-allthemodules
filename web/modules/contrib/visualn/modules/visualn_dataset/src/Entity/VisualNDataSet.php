<?php

namespace Drupal\visualn_dataset\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the VisualN Data Set entity.
 *
 * @ingroup visualn_dataset
 *
 * @ContentEntityType(
 *   id = "visualn_dataset",
 *   label = @Translation("VisualN Data Set"),
 *   bundle_label = @Translation("VisualN Data Set type"),
 *   handlers = {
 *     "storage" = "Drupal\visualn_dataset\VisualNDataSetStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\visualn_dataset\VisualNDataSetListBuilder",
 *     "views_data" = "Drupal\visualn_dataset\Entity\VisualNDataSetViewsData",
 *     "translation" = "Drupal\visualn_dataset\VisualNDataSetTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\visualn_dataset\Form\VisualNDataSetForm",
 *       "add" = "Drupal\visualn_dataset\Form\VisualNDataSetForm",
 *       "edit" = "Drupal\visualn_dataset\Form\VisualNDataSetForm",
 *       "delete" = "Drupal\visualn_dataset\Form\VisualNDataSetDeleteForm",
 *     },
 *     "access" = "Drupal\visualn_dataset\VisualNDataSetAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\visualn_dataset\VisualNDataSetHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "visualn_dataset",
 *   data_table = "visualn_dataset_field_data",
 *   revision_table = "visualn_dataset_revision",
 *   revision_data_table = "visualn_dataset_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer visualn data set entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/visualn/dataset/{visualn_dataset}",
 *     "add-page" = "/admin/visualn/dataset/add",
 *     "add-form" = "/admin/visualn/dataset/add/{visualn_dataset_type}",
 *     "edit-form" = "/admin/visualn/dataset/{visualn_dataset}/edit",
 *     "delete-form" = "/admin/visualn/dataset/{visualn_dataset}/delete",
 *     "version-history" = "/admin/visualn/dataset/{visualn_dataset}/revisions",
 *     "revision" = "/admin/visualn/dataset/{visualn_dataset}/revisions/{visualn_dataset_revision}/view",
 *     "revision_revert" = "/admin/visualn/dataset/{visualn_dataset}/revisions/{visualn_dataset_revision}/revert",
 *     "revision_delete" = "/admin/visualn/dataset/{visualn_dataset}/revisions/{visualn_dataset_revision}/delete",
 *     "translation_revert" = "/admin/visualn/dataset/{visualn_dataset}/revisions/{visualn_dataset_revision}/revert/{langcode}",
 *     "collection" = "/admin/visualn/datasets",
 *   },
 *   bundle_entity_type = "visualn_dataset_type",
 *   field_ui_base_route = "entity.visualn_dataset_type.edit_form"
 * )
 */
class VisualNDataSet extends RevisionableContentEntityBase implements VisualNDataSetInterface {

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
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly, make the visualn_dataset owner the
    // revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
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

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the VisualN Data Set entity.'))
      ->setRequired(TRUE)
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
      ->setDescription(t('The name of the VisualN Data Set entity.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
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

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the VisualN Data Set is published.'))
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

    // add default resource provider field and use it by defualt on entity type config page
    // @todo: it doesn't create a separate database table,
    //  instead adds columns to the visualn_dataset_field_data table
    $fields['resource_provider'] = BaseFieldDefinition::create('visualn_resource_provider')
      ->setLabel(t('Default resource provider'))
      ->setDescription(t('Default resource provider field for the dataset.'))
      //->setSettings([])
      //->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'visualn_resource_provider',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'visualn_resource_provider',
        'weight' => -4,
      ])
      ->setRequired(FALSE)
      ->setCardinality(1)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
      // @todo: should it be translatable?
      //   same for Name field
      //->setTranslatable(TRUE);
      //->setRevisionable(TRUE)

    return $fields;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: add to interface
   */
  public function getResourceProviderPlugin() {
    // @todo: there are multiple ways to get bundle entity type,
    //    see https://www.drupal.org/docs/8/api/entity-api/working-with-the-entity-api
    $bundle_entity_type = $this->getEntityType()->getBundleEntityType();
    $bundle = $this->bundle();

    // get config entity for the bundle
    $bundle_config_entity = \Drupal::entityTypeManager()->getStorage($bundle_entity_type)->load($bundle);

    // get resource provider field
    $resource_provider_field = $bundle_config_entity->getResourceProviderField();
    if (!empty($resource_provider_field)) {
      // get resource provider plugin instance or NULL
      // @todo: what if resource provider field has multiple items (can we also configure delta)?
      $resource_provider_plugin = $this->get($resource_provider_field)->first()->getResourceProviderPlugin();
    }

    return $resource_provider_plugin;
  }

}
