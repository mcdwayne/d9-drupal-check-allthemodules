<?php

namespace Drupal\user_attendance\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the User attendance entity.
 *
 * @ingroup user_attendance
 *
 * @ContentEntityType(
 *   id = "user_attendance",
 *   label = @Translation("User attendance"),
 *   bundle_label = @Translation("User attendance type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\user_attendance\UserAttendanceListBuilder",
 *     "views_data" = "Drupal\user_attendance\Entity\UserAttendanceViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\user_attendance\Form\UserAttendanceForm",
 *       "add" = "Drupal\user_attendance\Form\UserAttendanceForm",
 *       "edit" = "Drupal\user_attendance\Form\UserAttendanceForm",
 *       "delete" = "Drupal\user_attendance\Form\UserAttendanceDeleteForm",
 *     },
 *     "access" = "Drupal\user_attendance\UserAttendanceAccessController",
 *     "route_provider" = {
 *       "html" = "Drupal\user_attendance\UserAttendanceHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "user_attendance",
 *   admin_permission = "administer user attendance entities",
 *   permission_granularity = "bundle",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/people/user_attendance/{user_attendance}",
 *     "add-page" = "/admin/people/user_attendance/add",
 *     "add-form" = "/admin/people/user_attendance/add/{user_attendance_type}",
 *     "edit-form" = "/admin/people/user_attendance/{user_attendance}/edit",
 *     "delete-form" = "/admin/people/user_attendance/{user_attendance}/delete",
 *     "collection" = "/admin/people/user_attendance",
 *   },
 *   bundle_entity_type = "user_attendance_type",
 *   field_ui_base_route = "entity.user_attendance_type.edit_form"
 * )
 */
class UserAttendance extends ContentEntityBase implements UserAttendanceInterface {

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
  public function getStartTime() {
    return $this->get('start')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setStartTime($timestamp) {
    $this->set('start', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndTime() {
    return $this->get('end')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEndTime($timestamp) {
    $this->set('end', $timestamp);
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
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Attendance by'))
      ->setDescription(t('The user ID of the User attendance entity.'))
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

    $fields['start'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Start timestamp'))
      ->setDescription(t('The time that the attendance starts.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['end'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('End timestamp'))
      ->setDescription(t('The time that the attendance ends was created.'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
