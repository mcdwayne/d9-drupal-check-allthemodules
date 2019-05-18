<?php

namespace Drupal\flashpoint_course_content\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Flashpoint community content edit forms.
 *
 * @ingroup flashpoint_community_content
 */
class FlashpointCourseContentProgressButtons extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'flashpoint_course_progress_buttons';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $content = NULL, $course = NULL) {
    if ($content && $course) {
      $flashpoint_config = \Drupal::configFactory()->getEditable('flashpoint.settings');
      $content_settings = $flashpoint_config->getOriginal('course_content_render_options');

      $plugin_manager = \Drupal::service('plugin.manager.flashpoint_course_content_renderer');
      $plugin_definitions = $plugin_manager->getDefinitions();
      // Use the default if this isn't set.
      $render_plugin = !empty($content_settings['default']['renderer_progress']) ? $content_settings['default']['renderer_progress'] : 'flashpoint_course_content_default_renderer';
      $render_plugin = !empty($content_settings[$content->bundle()]['renderer_progress']) ? $content_settings['default']['renderer_progress'] : $render_plugin;

      $form['progress_buttons'] = $plugin_definitions[$render_plugin]['class']::renderProgressButtons($content, $course);
    }
    else {
      $form = [];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
