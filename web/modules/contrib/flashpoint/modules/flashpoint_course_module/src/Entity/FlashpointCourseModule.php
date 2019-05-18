<?php

namespace Drupal\flashpoint_course_module\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\flashpoint_course\FlashpointCourseUtilities;
use Drupal\flashpoint_course_content\Entity\FlashpointCourseContent;
use Drupal\group\Entity\GroupContent;
use Drupal\user\UserInterface;

/**
 * Defines the Flashpoint Course module entity.
 *
 * @ingroup flashpoint_course_module
 *
 * @ContentEntityType(
 *   id = "flashpoint_course_module",
 *   label = @Translation("Flashpoint Course module"),
 *   handlers = {
 *     "storage" = "Drupal\flashpoint_course_module\FlashpointCourseModuleStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\flashpoint_course_module\FlashpointCourseModuleListBuilder",
 *     "views_data" = "Drupal\flashpoint_course_module\Entity\FlashpointCourseModuleViewsData",
 *     "translation" = "Drupal\flashpoint_course_module\FlashpointCourseModuleTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\flashpoint_course_module\Form\FlashpointCourseModuleForm",
 *       "add" = "Drupal\flashpoint_course_module\Form\FlashpointCourseModuleForm",
 *       "edit" = "Drupal\flashpoint_course_module\Form\FlashpointCourseModuleForm",
 *       "delete" = "Drupal\flashpoint_course_module\Form\FlashpointCourseModuleDeleteForm",
 *     },
 *     "access" = "Drupal\flashpoint_course_module\FlashpointCourseModuleAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\flashpoint_course_module\FlashpointCourseModuleHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "flashpoint_course_module",
 *   data_table = "flashpoint_course_module_field_data",
 *   revision_table = "flashpoint_course_module_revision",
 *   revision_data_table = "flashpoint_course_module_field_revision",
 *   translatable = TRUE,
 *   admin_permission = "administer flashpoint course module entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/flashpoint_course_module/{flashpoint_course_module}",
 *     "add-form" = "/flashpoint_course_module/add",
 *     "edit-form" = "/flashpoint_course_module/{flashpoint_course_module}/edit",
 *     "delete-form" = "/flashpoint_course_module/{flashpoint_course_module}/delete",
 *     "version-history" = "/flashpoint_course_module/{flashpoint_course_module}/revisions",
 *     "revision" = "/flashpoint_course_module/{flashpoint_course_module}/revisions/{course_module_revision}/view",
 *     "revision_revert" = "/flashpoint_course_module/{flashpoint_course_module}/revisions/{course_module_revision}/revert",
 *     "revision_delete" = "/flashpoint_course_module/{flashpoint_course_module}/revisions/{course_module_revision}/delete",
 *     "translation_revert" = "/flashpoint_course_module/{flashpoint_course_module}/revisions/{course_module_revision}/revert/{langcode}",
 *     "collection" = "/flashpoint_course_module",
 *   },
 *   field_ui_base_route = "flashpoint_course_module.settings"
 * )
 */
class FlashpointCourseModule extends RevisionableContentEntityBase implements FlashpointCourseModuleInterface {

  use EntityChangedTrait;

  /**
   * @param $account
   * @return string
   *   The pass status, which may be 'neutral', 'locked', 'pending', or 'passed'.
   */
  public function getPassStatus($account) {
    if ($this->isNeutral($account)) {
      return 'neutral';
    }
    elseif ($this->isPassed($account)) {
      return 'passed';
    }
    elseif ($this->isLocked($account)) {
      return 'locked';
    }
    else {
      return 'pending';
    }
  }

  /**
   * Checks for cases when pending/passed status should not be shown.
   * @param $account
   * @return bool
   *   May be TRUE or FALSE
   */
  public function isNeutral($account) {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('flashpoint_lrs_client')) {
      $course = $this->getCourse();
      if (FlashpointCourseUtilities::trackCourseProgress($course) && !$account->isAnonymous()) {
        return FALSE;
      }
    }
    // If there is no LRS, then content is always "pending"
    return TRUE;
  }

  /**
   * @return bool
   * May be TRUE or FALSE
   */
  public function isPassed($account) {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('flashpoint_lrs_client') && !$this->isNeutral($account)) {
      // First, check if we are locked. We cannot pass until the section is unlocked.
      if ($this->isLocked($account)) {
        return FALSE;
      }
      $config_data = \Drupal::configFactory()->get('flashpoint.settings')->getRawData();
      $plugin_manager = \Drupal::service('plugin.manager.flashpoint_lrs_client');
      $plugin_definitions = $plugin_manager->getDefinitions();
      $plugin = isset($plugin_definitions[$config_data['lrs_client']]['class']) ? $plugin_definitions[$config_data['lrs_client']]['class'] : 'default';

      $pass_status = $plugin::checkPassStatus($account, $this, $config_data);
      if (!$pass_status) {
        // If we haven't finished all required content, we haven't passed yet
        if (!($this->instructionalContentIsPassed($account) &&
          $this->examinationContentIsPassed($account) &&
          $this->prerequisiteModulesPassed($account)
        )) {
          return FALSE;
        }
        $course = $this->getCourse();
        // In this case, we have in fact passed this module. Mark it passed.
        $event_data = [
          'actor' => [
            'source_id' => $account->id(),
            'name' => $account->getAccountName(),
            'type' => 'user',
          ],
          'action' => [
            'name' => 'passed',
            'type' => 'flashpoint_course_action'
          ],
          'object' => [
            'source_id' => $this->id(),
            'name' => $this->label(),
            'type' => $this->getEntityTypeId(),
          ],
          'context' => [
            'source_id' => $course->id(),
            'name' => $course->label(),
            'type' => $course->getEntityTypeId(),
          ]
        ];
        $post = $plugin::recordEvent($account, $this, $event_data, $config_data);

        return TRUE;
      }
      else {
        return $pass_status;
      }
    }
    // If there is no LRS, then content is always "pending"
    return FALSE;
  }

  /**
   * @param string $context
   * @param string $load_entities
   * @return array
   */
  public function getCourseContent($context = FALSE, $load_entities = FALSE) {
    $ret = [];
    $context_list = $context ? [$context] : ['instructional', 'examination'];
    foreach ($context_list as $context) {
      $ret[$context] = [];
      $content_data = $this->get('field_' . $context . '_content')->getValue();
      foreach ($content_data as $i => $c) {
        if ($load_entities) {
          $ret[$context][] = FlashpointCourseContent::load($c['target_id']);
        }
        else {
          $ret[$context][] = $c['target_id'];
        }
      }
    }

    // All required items are passed.
    return $ret;
  }

  /**
   * @param $account
   * @return bool
   */
  public function instructionalContentIsPassed($account) {
    $inst_content = $this->get('field_instructional_content')->getValue();
    foreach ($inst_content as $i => $c) {
      $content = FlashpointCourseContent::load($c['target_id']);
      // We hit a required item
      if($content->requiredToPass() && !$content->isPassed($account)) {
        return FALSE;
      }
    }
    // All required items are passed.
    return TRUE;
  }

  /**
   * @param $account
   * @return bool
   */
  public function examinationContentIsPassed($account) {
    $exam_content = $this->get('field_examination_content')->getValue();
    foreach ($exam_content as $i => $c) {
      $content = FlashpointCourseContent::load($c['target_id']);
      // We hit a required item.
      if($content->requiredToPass() && !$content->isPassed($account)) {
        return FALSE;
      }
    }
    // All required items are passed.
    return TRUE;
  }

  /**
   * @param $account
   * @return bool
   */
  public function prerequisiteModulesPassed($account) {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('flashpoint_lrs_client')) {
      $prereqs = $this->get('field_prerequisite_modules')->getValue();
      /*
       * Note, for access, we only check if a module is a prerequisite.
       * The "required" status is for the course pass logic.
       */
      foreach ($prereqs as $i => $p) {
        $module = FlashpointCourseModule::load($p['target_id']);
        // Sanity check: self-referenced modules will give an infinite loop
        if ($module->id() !== $this->id()) {
          // We must pass prerequisites first
          if(!$module->isPassed($account)) {
            return FALSE;
          }
        }
      }
    }
    // All required items are passed, or we have no LRS.
    return TRUE;
  }

  /**
   * @return bool
   */
  public function isLocked($account) {
    if (!$this->isNeutral($account)) {
      // Lock the module if required prerequisites are not passed.
      return !$this->prerequisiteModulesPassed($account);
    }
    else {
      return FALSE;
    }
  }

  /**
   * @return bool
   */
  public function requiredToPass() {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('flashpoint_lrs_client')) {
      $req = $this->get('required_to_pass')->getValue();
      if (isset($req[0]['value']) && $req[0]['value']) {
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    // If there is no LRS, then content is never "required_to_pass".
    return FALSE;
  }

  /**
   * @return \Drupal\group\Entity\Group
   */
  public function getCourse() {
    $gc = GroupContent::loadByEntity($this);
    $course = array_shift($gc)->getGroup();

    return $course;
  }

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

    // If no revision author has been set explicitly, make the course_module owner the
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
      ->setDescription(t('The user ID of author of the Course module entity.'))
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
      ->setDescription(t('The name of the Course module entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 255,
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

    $fields['required_to_pass'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Required to Pass'))
      ->setDescription(t('A boolean indicating whether the Flashpoint course content is required to pass the course.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Course module is published.'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
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
