<?php

namespace Drupal\splashify\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Splashify entity entity.
 *
 * @ingroup splashify
 *
 * @ContentEntityType(
 *   id = "splashify_entity",
 *   label = @Translation("Splashify entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\splashify\SplashifyEntityListBuilder",
 *     "views_data" = "Drupal\splashify\Entity\SplashifyEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\splashify\Form\SplashifyEntityForm",
 *       "add" = "Drupal\splashify\Form\SplashifyEntityForm",
 *       "edit" = "Drupal\splashify\Form\SplashifyEntityForm",
 *       "delete" = "Drupal\splashify\Form\SplashifyEntityDeleteForm",
 *     },
 *     "access" = "Drupal\splashify\SplashifyEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\splashify\SplashifyEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "splashify_entity",
 *   admin_permission = "administer Splashify entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/splashify_entity/{splashify_entity}",
 *     "add-form" = "/admin/structure/splashify_entity/add",
 *     "edit-form" = "/admin/structure/splashify_entity/{splashify_entity}/edit",
 *     "delete-form" = "/admin/structure/splashify_entity/{splashify_entity}/delete",
 *     "collection" = "/admin/structure/splashify_entity",
 *   },
 * )
 */
class SplashifyEntity extends ContentEntityBase implements SplashifyEntityInterface {

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
  public function getContent() {
    return $this->get('field_content')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupId() {
    return $this->get('field_group')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    $group_id = $this->get('field_group')->target_id;
    return SplashifyGroupEntity::load($group_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Splashify entity entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\node\Entity\Node::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'author',
        'weight' => -5,
      ))
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Splashify entity entity.'))
      ->setRequired(TRUE)
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
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Splashify entity is published.'))
      ->setDefaultValue(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['field_content'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Content'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', array(
        'type' => 'text_default',
        'label' => 'hidden',
        'weight' => -3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'text_textarea',
        'weight' => -3,
        'settings' => array(
          'rows' => 5,
        ),
      ))
      ->setDisplayConfigurable('from', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['field_group'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Group'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setSetting('target_type', 'splashify_group_entity')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', array(
        'type' => 'entity_reference_label',
        'label' => 'inline',
        'weight' => -2,
        'settings' => array(
          'link' => TRUE,
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'entity_reference_autocomplete',
        'weight' => -2,
        'settings' => array(
          'match_operator' => 'CONTAINS',
          'size' => 60,
        ),
      ))
      ->setDisplayConfigurable('from', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    $fields['field_weight'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Weight'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setSetting('min', -20)
      ->setSetting('max', 20)
      ->setDefaultValue(0)
      ->setDisplayOptions('view', array(
        'type' => 'number_integer',
        'label' => 'hidden',
        'weight' => -1,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'number',
        'weight' => -1,
      ))
      ->setDisplayConfigurable('from', FALSE)
      ->setDisplayConfigurable('view', FALSE);

    return $fields;
  }

}
