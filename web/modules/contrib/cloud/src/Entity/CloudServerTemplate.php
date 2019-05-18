<?php

namespace Drupal\cloud\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Cloud Server Template entity.
 *
 * @ingroup cloud_server_template
 *
 * @ContentEntityType(
 *   id = "cloud_server_template",
 *   label = @Translation("Cloud Server Template"),
 *   bundle_label = @Translation("Cloud Server Template type"),
 *   handlers = {
 *     "storage" = "Drupal\cloud\CloudServerTemplateStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\cloud\Controller\CloudServerTemplateListBuilder",
 *     "views_data" = "Drupal\cloud\Entity\CloudServerTemplateViewsData",
 *     "translation" = "Drupal\cloud\CloudServerTemplateTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\cloud\Form\CloudServerTemplateForm",
 *       "add" = "Drupal\cloud\Form\CloudServerTemplateForm",
 *       "edit" = "Drupal\cloud\Form\CloudServerTemplateForm",
 *       "delete" = "Drupal\cloud\Form\CloudServerTemplateDeleteForm",
 *       "launch" = "Drupal\cloud\Form\CloudServerTemplateLaunchConfirm",
 *       "copy" = "Drupal\cloud\Form\CloudServerTemplateCopyConfirm",
 *     },
 *     "access" = "Drupal\cloud\Controller\CloudServerTemplateAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\cloud\Routing\CloudServerTemplateHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "cloud_server_template",
 *   data_table = "cloud_server_template_field_data",
 *   revision_table = "cloud_server_template_revision",
 *   revision_data_table = "cloud_server_template_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer cloud server template entities",
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
 *     "canonical" = "/clouds/design/server_template/{cloud_context}/{cloud_server_template}",
 *     "add-form" = "/clouds/design/server_template/add/{cloud_context}/{cloud_server_template_type}",
 *     "edit-form" = "/clouds/design/server_template/edit/{cloud_context}/{cloud_server_template}",
 *     "delete-form" = "/clouds/design/server_template/delete/{cloud_context}/{cloud_server_template}",
 *     "version-history" = "/clouds/design/server_template/{cloud_context}/{cloud_server_template}/revisions",
 *     "revision" = "/clouds/design/server_template/{cloud_context}/{cloud_server_template}/revisions/{cloud_server_template_revision}/view",
 *     "revision_revert" = "/clouds/design/server_template/{cloud_context}/{cloud_server_template}/revisions/{cloud_server_template_revision}/revert",
 *     "revision_delete" = "/clouds/design/server_template/{cloud_context}/{cloud_server_template}/revisions/{cloud_server_template_revision}/delete",
 *     "translation_revert" = "/clouds/design/server_template/{cloud_context}/{cloud_server_template}/revisions/{cloud_server_template_revision}/revert/{langcode}",
 *     "collection" = "/clouds/design/server_template/list/{cloud_context}",
 *     "launch" = "/clouds/design/server_template/{cloud_context}/{cloud_server_template}/launch",
 *     "copy" = "/clouds/design/server_template/{cloud_context}/{cloud_server_template}/copy"
 *   },
 *   bundle_entity_type = "cloud_server_template_type",
 *   field_ui_base_route = "entity.cloud_server_template_type.edit_form"
 * )
 */
class CloudServerTemplate extends RevisionableContentEntityBase implements CloudServerTemplateInterface {

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

    // Add in cloud context.
    $uri_route_parameters['cloud_context'] = $this->getCloudContext();

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

    // If no revision author has been set explicitly, make the
    // cloud_server_template owner the revision author.
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
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Server Template.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 11,
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
      ->setDescription(t('The name of the server template.'))
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

    // @TODO: make this an entity reference to config entity?  For now, leave as string
    $fields['cloud_context'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Cloud Provider Machine Name'))
      ->setRequired(TRUE)
      ->setDescription(t('A unique machine name for the cloud provider.'))
      ->setRevisionable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -3,
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Cloud Server Template is published.'))
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

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();
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

}
