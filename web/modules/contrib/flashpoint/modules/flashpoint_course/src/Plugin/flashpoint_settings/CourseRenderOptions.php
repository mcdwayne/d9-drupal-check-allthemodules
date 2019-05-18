<?php

namespace Drupal\flashpoint_course\Plugin\flashpoint_settings;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\flashpoint\FlashpointSettingsInterface;


/**
 * @FlashpointSettings(
 *   id = "course_render_options",
 *   label = @Translation("Course Render Options"),
 * )
 */
class CourseRenderOptions extends PluginBase implements FlashpointSettingsInterface
{
  /**
   * @return string
   *   A string description.
   */
  public function description()
  {
    return $this->t('Settings for the default course content renderer.');
  }

  /**
   * Provide form options for the settings form.
   * @return array
   *   Array of Form API form elements.
   */
  public static function getFormOptions() {
    $flashpoint_config = \Drupal::configFactory()->getEditable('flashpoint.settings');
    $plugin_manager = \Drupal::service('plugin.manager.flashpoint_course_renderer');
    $plugin_definitions = $plugin_manager->getDefinitions();
    $plugin_options = [];
    foreach ($plugin_definitions as $pd) {
      if ($pd['label'] instanceof TranslatableMarkup) {
        $plugin_options[$pd['id']] = $pd['label']->render();
      }
      else {
        $plugin_options[$pd['id']] = $pd['label'];
      }
    }
    $ret = [
      'course_render_options' => [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => t('Course Render Options'),
        '#group' => 'flashpoint',
        'flashpoint_course__renderer' => [
          '#type' => 'select',
          '#title' => t('Default Renderer'),
          '#description' => t('The default render plugin for all courses.'),
          '#empty_option' => t(' - Select - '),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course.renderer'),
          '#options' => $plugin_options,
        ],

        'flashpoint_course__neutral_class' => [
          '#type' => 'textfield',
          '#title' => t('Neutral Status Class'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course.neutral_class'),
        ],
        'flashpoint_course__neutral_icon' => [
          '#type' => 'textfield',
          '#title' => t('Neutral Icon'),
          '#description' => t('HTML of the neutral icon'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course.neutral_icon'),
        ],
        'flashpoint_course__lock_class' => [
          '#type' => 'textfield',
          '#title' => t('Locked Status Class'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course.lock_class'),
        ],
        'flashpoint_course__lock_icon' => [
          '#type' => 'textfield',
          '#title' => t('Lock Icon'),
          '#description' => t('HTML of the locked status icon'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course.lock_icon'),
        ],
        'flashpoint_course__pending_class' => [
          '#type' => 'textfield',
          '#title' => t('Pending Status Class'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course.pending_class'),
        ],
        'flashpoint_course__pending_icon' => [
          '#type' => 'textfield',
          '#title' => t('Pending Icon'),
          '#description' => t('HTML of the pending status icon'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course.pending_icon'),
        ],
        'flashpoint_course__passed_class' => [
          '#type' => 'textfield',
          '#title' => t('Passed Status Class'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course.passed_class'),
        ],
        'flashpoint_course__passed_icon' => [
          '#type' => 'textfield',
          '#title' => t('Passed Icon'),
          '#description' => t('HTML of the passed status icon'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course.passed_icon'),
        ],
      ],
    ];
    return $ret;
  }

}