<?php

namespace Drupal\cloud\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\cloud\CloudContextInterface;

/**
 * Defines the Cloud config entity.
 *
 * @ingroup cloud
 *
 * @ContentEntityType(
 *   id = "cloud_config",
 *   label = @Translation("Cloud config"),
 *   bundle_label = @Translation("Cloud config type"),
 *   handlers = {
 *     "storage" = "Drupal\cloud\CloudConfigStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\cloud\Controller\CloudConfigListBuilder",
 *     "views_data" = "Drupal\cloud\Entity\CloudConfigViewsData",
 *     "translation" = "Drupal\cloud\CloudConfigTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\cloud\Form\CloudConfigForm",
 *       "add" = "Drupal\cloud\Form\CloudConfigForm",
 *       "edit" = "Drupal\cloud\Form\CloudConfigForm",
 *       "delete" = "Drupal\cloud\Form\CloudConfigDeleteForm",
 *     },
 *     "access" = "Drupal\cloud\Controller\CloudConfigAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\cloud\Routing\CloudConfigHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "cloud_config",
 *   data_table = "cloud_config_field_data",
 *   revision_table = "cloud_config_revision",
 *   revision_data_table = "cloud_config_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer cloud config entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/cloud_config/{cloud_config}",
 *     "add-page" = "/admin/structure/cloud_config/add",
 *     "add-form" = "/admin/structure/cloud_config/add/{cloud_config_type}",
 *     "edit-form" = "/admin/structure/cloud_config/{cloud_config}/edit",
 *     "delete-form" = "/admin/structure/cloud_config/{cloud_config}/delete",
 *     "version-history" = "/admin/structure/cloud_config/{cloud_config}/revisions",
 *     "revision" = "/admin/structure/cloud_config/{cloud_config}/revisions/{cloud_config_revision}/view",
 *     "revision_revert" = "/admin/structure/cloud_config/{cloud_config}/revisions/{cloud_config_revision}/revert",
 *     "revision_delete" = "/admin/structure/cloud_config/{cloud_config}/revisions/{cloud_config_revision}/delete",
 *     "translation_revert" = "/admin/structure/cloud_config/{cloud_config}/revisions/{cloud_config_revision}/revert/{langcode}",
 *     "collection" = "/admin/structure/cloud_config",
 *   },
 *   bundle_entity_type = "cloud_config_type",
 *   field_ui_base_route = "entity.cloud_config_type.edit_form"
 * )
 */
class CloudConfig extends RevisionableContentEntityBase implements CloudConfigInterface, CloudContextInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
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

    // If no revision author has been set explicitly,
    // make the cloud_config owner the revision author.
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
    return $this->get('uid')->entity;
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
  public function getCloudContext() {
    return $this->get('cloud_context')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCloudContext($cloud_context) {
    $this->set('cloud_context', $cloud_context);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();
    $this->deleteServerTemplate();
    $this->updateCache();
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $return = parent::save();
    $this->updateCache();
    return $return;
  }

  /**
   * Clear the menu, render cache and rebuild the routers.
   */
  private function updateCache() {
    // Clear block and menu cache.
    \Drupal::cache('menu')->invalidateAll();
    \Drupal::service('cache.render')->deleteAll();
    \Drupal::service('router.builder')->rebuild();
    \Drupal::service('plugin.cache_clearer')->clearCachedDefinitions();
  }

  /**
   * Delete server templates after cloud config deletion.
   */
  private function deleteServerTemplate() {
    $ids = \Drupal::entityQuery('cloud_server_template')
      ->condition('cloud_context', $this->getCloudContext())
      ->execute();
    if (count($ids)) {
      /* @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager */
      $entity_manager = \Drupal::entityTypeManager();
      $entities = $entity_manager->getStorage('cloud_server_template')
        ->loadMultiple($ids);
      $entity_manager->getStorage('cloud_server_template')->delete($entities);
    }
  }

  /**
   * Check if a specific cloud_context exists.
   *
   * Corresponds with the #machine_name widget type
   * when adding a new CloudConfig.
   *
   * @param string $cloud_context
   *   The cloud context.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   The cloud config entities.
   */
  public static function checkCloudContext($cloud_context) {
    $entity_type_manager = \Drupal::entityTypeManager();
    $storage = $entity_type_manager->getStorage('cloud_config');
    return $storage->loadByProperties(['cloud_context' => [$cloud_context]]);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Cloud config entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 99,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 99,
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
      ->setDescription(t('The name of the Cloud config entity.'))
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
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['cloud_context'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine Name'))
      ->setRequired(TRUE)
      ->setDescription(t('A unique machine name for the cloud provider.'))
      ->setRevisionable(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Cloud config is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 100,
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
