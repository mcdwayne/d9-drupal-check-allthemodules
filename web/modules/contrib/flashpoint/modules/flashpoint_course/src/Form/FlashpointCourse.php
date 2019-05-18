<?php

namespace Drupal\flashpoint_course\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Flashpoint community content edit forms.
 *
 * @ingroup flashpoint_community_content
 */
class FlashpointCourse extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flashpoint_course';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $course = NULL) {
    $flashpoint_config = \Drupal::configFactory()->getEditable('flashpoint.settings');
    $course_settings = $flashpoint_config->getOriginal('flashpoint_course');

    $plugin_manager = \Drupal::service('plugin.manager.flashpoint_course_renderer');
    $plugin_definitions = $plugin_manager->getDefinitions();
    // Use the default if this isn't set.
    $render_plugin = !empty($course_settings['renderer']) ? $course_settings['renderer'] : 'default_flashpoint_course_renderer';

    $form['course'] = $plugin_definitions[$render_plugin]['class']::renderCourse($course);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
