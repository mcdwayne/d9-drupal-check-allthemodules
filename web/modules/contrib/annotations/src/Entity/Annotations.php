<?php

namespace Drupal\annotations\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\annotations\AnnotationsInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the Annotations entity.
 *
 * @ingroup annotations
 *
 *
 * @ContentEntityType(
 *   id = "annotations",
 *   label = @Translation("Annotations"),
 *   handlers = {
 *     "list_builder" = "Drupal\annotations\Entity\Controller\AnnotationsListBuilder",
 *     "form" = {
 *       "add" = "Drupal\annotations\Form\AnnotationsForm",
 *       "edit" = "Drupal\annotations\Form\AnnotationsForm",
 *       "delete" = "Drupal\annotations\Form\AnnotationsDeleteForm",
 *     },
 *     "access" = "Drupal\annotations\AnnotationsAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "annotations",
 *   admin_permission = "administer annotations entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "/annotations/{annotations}/edit",
 *     "delete-form" = "/annotations/{annotations}/delete",
 *     "collection" = "/annotations"
 *   },
 *   field_ui_base_route = "annotations.annotations_settings",
 * )
 *
 */
class Annotations extends ContentEntityBase implements AnnotationsInterface {

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
  public function getType() {
    return $this->get('type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setType($type) {
    $this->set('type', $type);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the annotation entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the annotation entity.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setRequired(true)
      ->setDescription(t('The annotation type.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setReadOnly(TRUE);


    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setRequired(true)
      ->setDescription(t('The name of the annotation entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue(NULL)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setRequired(true)
      ->setDescription(t('The footnote.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'text_long',
        'weight' => -4,

      ))
      ->setDisplayOptions('form', array(
        'type' => 'text_long',
        'weight' => -4,
        'settings' => array(
          'placeholder' => '',
        ),
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The language code of annotation.'));
    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the annotation was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the annotation was last edited.'));

    return $fields;
  }

}
