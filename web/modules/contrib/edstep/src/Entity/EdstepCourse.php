<?php

namespace Drupal\edstep\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;
use Edstep\Course;
use Drupal\Core\Url;

/**
 * Defines the EdstepCourse entity.
 *
 * @ingroup edstep
 *
 * @ContentEntityType(
 *   id = "edstep_course",
 *   label = @Translation("EdStep Course"),
 *   base_table = "edstep_course",
 *   handlers = {
 *     "view_builder" = "Drupal\edstep\EdstepCourseViewBuilder",
 *     "views_data" = "Drupal\edstep\EdstepCourseViewsData",
 *     "access" = "Drupal\edstep\EdstepCourseAccessControlHandler",
 *     "list_builder" = "Drupal\edstep\EdstepCourseListBuilder",
 *     "form" = {
 *       "default" = "Drupal\edstep\Form\EdstepCourseForm",
 *       "add_button" = "Drupal\edstep\Form\EdstepCourseAddButtonForm",
 *       "remove_button" = "Drupal\edstep\Form\EdstepCourseDeleteButtonForm",
 *       "delete" = "Drupal\edstep\Form\EdstepCourseDeleteForm",
 *     },
 *   },
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "course_id",
 *     "uuid" = "uuid",
 *     "uid" = "uid",
 *   },
 *   links = {
 *     "canonical" = "/edstep/course/{edstep_course}",
 *     "delete-form" = "/edstep/course/{edstep_course}/delete",
 *   },
 *   persistent_cache = FALSE,
 *   field_ui_base_route = "entity.edstep_course.edit_form",
 * )
 */
class EdstepCourse extends ContentEntityBase implements ContentEntityInterface, EntityOwnerInterface {

  protected $remote;

  public function getClient() {
    return \Drupal::service('edstep.edstep')->getClient();
  }

  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    $fields['course_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('EdStep course ID'))
      ->setDescription(t('The ID of the course in EdStep.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the course.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Added on'))
      ->setDescription(t('The time that the course was added.'))
      ->setDefaultValueCallback('time');

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Added by'))
      ->setDescription(t('The user that added the course.'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\edstep\Entity\EdstepCourse::getCurrentUserId');

    return $fields;
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  public function getRemote() {
    if(!isset($this->remote)) {
      if($this->id()) {
        $this->remote = $this->getClient()->course($this->id());
      } else {
        $this->remote = NULL;
      }
    }
    return $this->remote;
  }

  public function setRemote(Course $remote) {
    $this->course_id = $remote->id;
    $this->remote = $remote;
  }

  public function getTitle() {
    return $this->getRemoteValue('title');
  }

  public function label() {
    return $this->getRemoteValue('title');
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
    return $this->getEntityKey('uid');
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

  public function getRemoteValue($key) {
    try {
      return $this->getRemote()->{$key};
    } catch(RequestException $e) {
      return NULL;
    }
  }

  public function getActivityUrl($section_id, $activity_id) {
    return Url::fromRoute('entity.edstep_course.view_activity', [
      'edstep_course' => $this->id(),
      'section_id' => $section_id,
      'activity_id' => $activity_id,
    ]);
  }

  public function getActivityResourceUrl($section_id, $activity_id) {
    return \Drupal::service('edstep.edstep')->getActivityResourceUrl($this->getRemote(), $section_id, $activity_id);
  }

  public function getContinueUrl() {
    $sections = $this->getRemoteValue('sections');
    $section = $sections[0];
    $activity = $section->activities[0];
    return $this->getActivityUrl($section->id, $activity->id);
  }

  public function isEnrolled() {
    return $this->getRemoteValue('enrolled');
  }

  public function onChange($name) {
    if($name == 'course_id') {
      unset($this->remote);
    }
    return parent::onChange($name);
  }

  public function __sleep() {
    unset($this->remote);
    return parent::__sleep();
  }

}
