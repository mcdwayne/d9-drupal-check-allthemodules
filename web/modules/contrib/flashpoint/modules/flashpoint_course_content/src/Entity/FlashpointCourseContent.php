<?php

namespace Drupal\flashpoint_course_content\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\flashpoint_course\FlashpointCourseUtilities;
use Drupal\group\Entity\GroupContent;
use Drupal\user\UserInterface;

/**
 * Defines the Flashpoint course content entity.
 *
 * @ingroup flashpoint_course_content
 *
 * @ContentEntityType(
 *   id = "flashpoint_course_content",
 *   label = @Translation("Flashpoint course content"),
 *   bundle_label = @Translation("Flashpoint course content type"),
 *   handlers = {
 *     "view_builder" = "Drupal\flashpoint_course_content\FlashpointCourseContentViewBuilder",
 *     "list_builder" = "Drupal\flashpoint_course_content\FlashpointCourseContentListBuilder",
 *     "views_data" = "Drupal\flashpoint_course_content\Entity\FlashpointCourseContentViewsData",
 *     "translation" = "Drupal\flashpoint_course_content\FlashpointCourseContentTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\flashpoint_course_content\Form\FlashpointCourseContentForm",
 *       "add" = "Drupal\flashpoint_course_content\Form\FlashpointCourseContentForm",
 *       "edit" = "Drupal\flashpoint_course_content\Form\FlashpointCourseContentForm",
 *       "delete" = "Drupal\flashpoint_course_content\Form\FlashpointCourseContentDeleteForm",
 *     },
 *     "access" = "Drupal\flashpoint_course_content\FlashpointCourseContentAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\flashpoint_course_content\FlashpointCourseContentHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "flashpoint_course_content",
 *   data_table = "flashpoint_course_content_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer flashpoint course content entities",
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
 *     "canonical" = "/flashpoint_course_content/{flashpoint_course_content}",
 *     "add-page" = "/flashpoint_course_content/add",
 *     "add-form" = "/flashpoint_course_content/add/{flashpoint_course_content_type}",
 *     "edit-form" = "/flashpoint_course_content/{flashpoint_course_content}/edit",
 *     "delete-form" = "/flashpoint_course_content/{flashpoint_course_content}/delete",
 *     "collection" = "/admin/content/flashpoint/flashpoint_course_content",
 *   },
 *   bundle_entity_type = "flashpoint_course_content_type",
 *   field_ui_base_route = "entity.flashpoint_course_content_type.edit_form"
 * )
 */
class FlashpointCourseContent extends ContentEntityBase implements FlashpointCourseContentInterface {

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
   * @param $account
   * @return bool
   *   May be TRUE or FALSE
   */
  public function isPassed($account) {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('flashpoint_lrs_client')) {
      $course = $this->getCourse();
      if (FlashpointCourseUtilities::trackCourseProgress($course)) {
        $config_data = \Drupal::configFactory()->get('flashpoint.settings')->getRawData();

        $plugin_manager = \Drupal::service('plugin.manager.flashpoint_lrs_client');
        $plugin_definitions = $plugin_manager->getDefinitions();
        $plugin = isset($plugin_definitions[$config_data['lrs_client']]['class']) ? $plugin_definitions[$config_data['lrs_client']]['class'] : 'default';
        $pass_status = $plugin::checkPassStatus($account, $this, $config_data);
        return $pass_status;
      }
    }
    // If there is no LRS or tracking is off, then content is always "pending"
    return FALSE;
  }

  /**
   * @return bool
   */
  public function isLocked($account) {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('flashpoint_lrs_client')) {
      $course = $this->getCourse();
      if (!FlashpointCourseUtilities::isOpenAccessCourse($course) && FlashpointCourseUtilities::trackCourseProgress($course)) {
        $cm = $this->getCourseModule();
        if ($cm) {
          switch ($cm['type']) {
            case 'instructional':
              return $cm['module']->isLocked($account);
              break;
            case 'examination':
              return $cm['module']->isLocked($account) || $cm['module']->instructionalContentIsPassed($account);
              break;
          }
        }
      }
    }
    /*
     * If there is no LRS and no course modules, then content is never locked.
     * Also, open courses and courses without tracking are never locked.
     */
    return FALSE;
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
   * @return mixed
   */
  public function getCourseModule() {
    //Check first if we have the module turned on.
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('flashpoint_course_module')) {
      $inst_query = \Drupal::entityQuery('flashpoint_course_module');
      $inst_query->condition('field_instructional_content', $this->id());
      $insts = $inst_query->execute();
      if ($insts) {
        $inst_vals = array_values($insts);
        $cmid = array_shift($inst_vals);
        return [
          'module' => \Drupal\flashpoint_course_module\Entity\FlashpointCourseModule::load($cmid),
          'type' => 'instructional'
        ];
      }
      else {
        $exam_query = \Drupal::entityQuery('flashpoint_course_module');
        $exam_query->condition('field_examination_content', $this->id());
        $exams = $exam_query->execute();
        if ($exams) {
          $cmid = array_shift(array_values($exams));
          return [
            'module' => \Drupal\flashpoint_course_module\Entity\FlashpointCourseModule::load($cmid),
            'type' => 'examination'
          ];
        }
      }
    }


    return FALSE;
  }

  /**
   * Render the course in a listing context.
   *
   * @param bool $user
   * @return mixed
   */
  public function renderListing($user = FALSE) {
    $user = $user ? $user : \Drupal::currentUser();

    $flashpoint_config = \Drupal::configFactory()->getEditable('flashpoint.settings');
    $content_settings = $flashpoint_config->getOriginal('flashpoint_course_content');
    $default_renderer = !empty($content_settings['default']['renderer_listing']) ? $content_settings['default']['renderer_listing']: 'flashpoint_course_content_default_renderer';

    $plugin_manager = \Drupal::service('plugin.manager.flashpoint_course_content_renderer');
    $plugin_definitions = $plugin_manager->getDefinitions();
    $bundle = $this->bundle();
    $renderer = !empty($content_settings[$bundle]['renderer_listing']) ? $content_settings[$bundle]['renderer_listing'] : $default_renderer;

    return $plugin_definitions[$renderer]['class']::renderListing($this, $user);
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
      ->setDescription(t('The user ID of author of the Flashpoint course content entity.'))
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
      ->setDescription(t('The name of the Flashpoint course content entity.'))
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
      ->setDescription(t('A boolean indicating whether the Flashpoint course content is published.'))
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

    return $fields;
  }

}
