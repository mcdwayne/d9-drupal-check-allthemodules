<?php

namespace Drupal\flashpoint_course_module\Plugin\GroupContentEnabler;

use Drupal\group\Plugin\GroupContentEnablerBase;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

/**
 * Allows NiobiResource content to be added to groups.
 *
 * @GroupContentEnabler(
 *   id = "flashpoint_course_group_flashpoint_course_module",
 *   label = @Translation("Course Modules"),
 *   description = @Translation("Adds flashpoint_course_module entities to groups."),
 *   entity_access = TRUE,
 *   entity_type_id = "flashpoint_course_module",
 *   path_key = "flashpoint_course_module",
 *   deriver = "Drupal\flashpoint_course_module\Plugin\GroupContentEnabler\FlashpointCourseGroupFlashpointCourseModuleDeriver"
 * )
 */
class FlashpointCourseGroupFlashpointCourseModule extends GroupContentEnablerBase {
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['entity_cardinality'] = 1;
    return $config;
  }
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    // Disable the entity cardinality field as the functionality of this module
    // relies on a cardinality of 1. We don't just hide it, though, to keep a UI
    // that's consistent with other content enabler plugins.
    $info = $this->t("This field has been disabled by the plugin to guarantee the functionality that's expected of it.");
    $form['entity_cardinality']['#disabled'] = TRUE;
    $form['entity_cardinality']['#description'] .= '<br /><em>' . $info . '</em>';
    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return ['module' => ['flashpoint_course_module']];
  }
}