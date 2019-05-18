<?php

namespace Drupal\eloqua_app_cloud\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Eloqua AppCloud Service entity.
 *
 * @ingroup eloqua_app_cloud
 *
 * @ContentEntityType(
 *   id = "eloqua_app_cloud_service",
 *   label = @Translation("Eloqua AppCloud Service"),
 *   bundle_label = @Translation("Eloqua AppCloud Service type"),
 *   handlers = {
 *     "view_builder" = "Drupal\eloqua_app_cloud\Entity\EloquaAppCloudServiceViewBuilder",
 *     "list_builder" = "Drupal\eloqua_app_cloud\EloquaAppCloudServiceListBuilder",
 *     "views_data" = "Drupal\eloqua_app_cloud\Entity\EloquaAppCloudServiceViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\eloqua_app_cloud\Form\EloquaAppCloudServiceForm",
 *       "add" = "Drupal\eloqua_app_cloud\Form\EloquaAppCloudServiceForm",
 *       "edit" = "Drupal\eloqua_app_cloud\Form\EloquaAppCloudServiceForm",
 *       "delete" = "Drupal\eloqua_app_cloud\Form\EloquaAppCloudServiceDeleteForm",
 *     },
 *     "access" = "Drupal\eloqua_app_cloud\EloquaAppCloudServiceAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\eloqua_app_cloud\EloquaAppCloudServiceHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "eloqua_app_cloud_service",
 *   admin_permission = "administer eloqua appcloud service entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/eloqua/hook/{eloqua_app_cloud_service}",
 *     "add-page" = "/admin/structure/eloqua_app_cloud_service/add",
 *     "add-form" = "/admin/structure/eloqua_app_cloud_service/add/{eloqua_app_cloud_service_type}",
 *     "edit-form" = "/admin/structure/eloqua_app_cloud_service/{eloqua_app_cloud_service}/edit",
 *     "delete-form" = "/admin/structure/eloqua_app_cloud_service/{eloqua_app_cloud_service}/delete",
 *     "collection" = "/admin/structure/eloqua_app_cloud_service",
 *   },
 *   bundle_entity_type = "eloqua_app_cloud_service_type",
 *   field_ui_base_route = "entity.eloqua_app_cloud_service_type.edit_form"
 * )
 */
class EloquaAppCloudService extends ContentEntityBase implements EloquaAppCloudServiceInterface {

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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Eloqua AppCloud Service entity.'))
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
      ->setDescription(t('The name of the Eloqua AppCloud Service entity.'))
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
      ->setDescription(t('A boolean indicating whether the Eloqua AppCloud Service is published.'))
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
