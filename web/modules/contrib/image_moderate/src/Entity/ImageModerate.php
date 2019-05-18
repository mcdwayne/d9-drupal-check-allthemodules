<?php

namespace Drupal\image_moderate\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\user\UserInterface;
use Drupal\image_moderate\ImageModerateInterface;

/**
 * Defines the ImageModerate entity.
 *
 * @ingroup image_moderate
 *
 * @ContentEntityType(
 * id = "image_moderate",
 * label = @Translation("Image Moderate entity"),
 * handlers = {
 * "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 * "list_builder" = "Drupal\image_moderate\Entity\Controller\ImageModerateListBuilder",
 * "form" = {
 * "add" = "Drupal\image_moderate\Form\ImageModerateForm",
 * "edit" = "Drupal\image_moderate\Form\ImageModerateForm",
 * "delete" = "Drupal\image_moderate\Form\ImageModerateDeleteForm",
 * },
 * "access" = "Drupal\image_moderate\ImageModerateAccessControlHandler",
 * },
 * list_cache_contexts = { "file" },
 * base_table = "image_moderate",
 * admin_permission = "administer image moderate entity",
 * entity_keys = {
 * "id" = "id",
 * "uuid" = "uuid",
 * },
 * links = {
 *     "canonical" = "/image_moderate/{image_moderate}",
 *     "edit-form" = "/image_moderate/{image_moderate}/edit",
 *     "delete-form" = "/image_moderate/{image_moderate}/delete",
 *     "collection" = "/image_moderate/list"
 * },
 * field_ui_base_route = "image_moderate.settings",
 * )
 */
class ImageModerate extends ContentEntityBase implements ImageModerateInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    // Default author to current user.
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
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
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setChangedTime($timestamp) {
    $this->set('changed', $timestamp);
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
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Image Moderate entity.'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Image Moderate entity.'))
      ->setReadOnly(TRUE);

    // The fid of the image.
    $fields['fid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('FID'))
      ->setDescription(t('The FID of the Image in the Image Moderate entity.'));

    // The entity_id of the image.
    $fields['entity_uuid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity_UUID'))
      ->setDescription(t('The Entity_UUID of the content containing the Image in the Image Moderate entity.'));

    // The entity_type of the image.
    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity_Type'))
      ->setDescription(t('The Entity_Type of the content containing the Image in the Image Moderate entity.'));

    // Owner field of the contact.
    // Entity reference field, holds the reference to the user object.
    // The view shows the user name field of the user.
    // The form presents a auto complete field for the user name.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Name'))
      ->setDescription(t('The Name of the associated user.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // The status of the image.
    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Status'))
      ->setDescription(t('The status of the Image in the Image Moderate entity. (0 = Needs review | 1 = Reviewed | 2 = Can not be published)'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['reviewed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Reviewed'))
      ->setDescription(t('The time that the entity was last reviewed.'));

    $fields['reviewed_by'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Reviewer'))
      ->setDescription(t('The Name of the associated user.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'user',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    return $fields;
  }

}
