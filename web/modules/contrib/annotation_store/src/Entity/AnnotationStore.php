<?php

namespace Drupal\annotation_store\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\annotation_store\AnnotationStoreInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Annotation for Annotation Store Entity.
 *
 * @ContentEntityType(
 *   id = "annotation_store",
 *   label = @Translation("Annotation entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\annotation_store\Entity\Controller\AnnotationStoreListBuilder",
 *     "access" = "Drupal\annotation_store\AnnotationStoreAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "annotation_store",
 *   admin_permission = "administer annotation_store entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "user",
 *   },
 *   links = {
 *     "canonical" = "/annotation_store/{annotation_store}",
 *     "collection" = "/annotation_store/list"
 *   },
 * )
 *
 * The AnnotationStore class defines methods and fields for the annotation_store
 * entity.
 */
class AnnotationStore extends ContentEntityBase implements AnnotationStoreInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
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
      ->setDescription(t('The ID of the Contact entity.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The type of the annotation.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 20,
        'text_processing' => 0,
      ));

    $fields['language'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Language'))
      ->setDescription(t('The language of the annotation.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 12,
        'text_processing' => 0,
      ));

    $fields['data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Data'))
      ->setDescription(t('Data includes container, extension, src, start and end time.'))
      ->setSettings(array(
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ));

    $fields['uri'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('URI'))
      ->setDescription(t('The first name of the Contact entity.'))
      ->setDefaultValue('');

    $fields['text'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Text'))
      ->setDescription(t('The type of the annotation.'))
      ->setDefaultValue('');

    $fields['resource_entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Resource Entity ID'))
      ->setDescription(t('Resource entity id.'))
      ->setDefaultValue('');

    // Entity reference field, holds the reference to the user object.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User Name'))
      ->setDescription(t('The Name of the associated user.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'author',
        'weight' => -3,
      ));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
