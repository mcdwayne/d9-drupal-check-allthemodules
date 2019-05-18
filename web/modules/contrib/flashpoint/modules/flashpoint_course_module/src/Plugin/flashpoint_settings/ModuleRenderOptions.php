<?php

namespace Drupal\flashpoint_course_module\Plugin\flashpoint_settings;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\flashpoint\FlashpointSettingsInterface;


/**
 * @FlashpointSettings(
 *   id = "course_module_render_options",
 *   label = @Translation("Course Module Render Options"),
 * )
 */
class ModuleRenderOptions extends PluginBase implements FlashpointSettingsInterface
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
    $ret = [
      'module_render_options' => [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => t('Course Module Render Options'),
        '#group' => 'flashpoint',
        'flashpoint_course_module__instructional_text' => [
          '#type' => 'textfield',
          '#title' => t('Text for "Instructional Content"'),
          '#description' => t('This is used for all instances of the phrase "Instructional Content" for the instructional content referenced by a module.'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_module.instructional_text') ? $flashpoint_config->getOriginal('flashpoint_course_module.instructional_text') : 'Instructional Content',
        ],
        'flashpoint_course_module__examination_text' => [
          '#type' => 'textfield',
          '#title' => t('Text for "Examination Content"'),
          '#description' => t('This is used for all instances of the phrase "Examination Content" for the instructional content referenced by a module.'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_module.examination_text') ? $flashpoint_config->getOriginal('flashpoint_course_module.examination_text') : 'Examination Content',
        ],
        'flashpoint_course_module__neutral_class' => [
          '#type' => 'textfield',
          '#title' => t('Neutral Status Class'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_module.neutral_class'),
        ],
        'flashpoint_course_module__neutral_icon' => [
          '#type' => 'textfield',
          '#title' => t('Neutral Icon'),
          '#description' => t('HTML of the neutral icon'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_module.neutral_icon'),
        ],
        'flashpoint_course_module__lock_class' => [
          '#type' => 'textfield',
          '#title' => t('Locked Status Class'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_module.lock_class'),
        ],
        'flashpoint_course_module__lock_icon' => [
          '#type' => 'textfield',
          '#title' => t('Lock Icon'),
          '#description' => t('HTML of the locked status icon'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_module.lock_icon'),
        ],
        'flashpoint_course_module__pending_class' => [
          '#type' => 'textfield',
          '#title' => t('Pending Status Class'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_module.pending_class'),
        ],
        'flashpoint_course_module__pending_icon' => [
          '#type' => 'textfield',
          '#title' => t('Pending Icon'),
          '#description' => t('HTML of the pending status icon'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_module.pending_icon'),
        ],
        'flashpoint_course_module__passed_class' => [
          '#type' => 'textfield',
          '#title' => t('Passed Status Class'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_module.passed_class'),
        ],
        'flashpoint_course_module__passed_icon' => [
          '#type' => 'textfield',
          '#title' => t('Passed Icon'),
          '#description' => t('HTML of the passed status icon'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_module.passed_icon'),
        ],
      ],
    ];
    return $ret;
  }

}