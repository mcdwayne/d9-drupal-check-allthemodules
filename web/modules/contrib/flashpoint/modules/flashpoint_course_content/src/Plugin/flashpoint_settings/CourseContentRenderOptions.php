<?php

namespace Drupal\flashpoint_course_content\Plugin\flashpoint_settings;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\flashpoint\FlashpointSettingsInterface;


/**
 * @FlashpointSettings(
 *   id = "course_content_render_options",
 *   label = @Translation("Course Content Render Options"),
 * )
 */
class CourseContentRenderOptions extends PluginBase implements FlashpointSettingsInterface
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
    $plugin_manager = \Drupal::service('plugin.manager.flashpoint_course_content_renderer');
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
      'course_content_render_options' => [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => t('Course Content Render Options'),
        'progress_buttons' => [
          '#type' => 'details',
          '#open' => TRUE,
          '#title' => t('Progress Button Settings'),
          '#description' => t('These settings apply to all course content of all types, and amy be used by any render plugin.'),
          'flashpoint_course_content__prev_text' => [
            '#type' => 'textfield',
            '#title' => t('Previous Button Text'),
            '#description' => t('The previous button text.'),
            '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.default.prev_text'),
          ],
          'flashpoint_course_content__return_text' => [
            '#type' => 'textfield',
            '#title' => t('Return Button Text'),
            '#description' => t('The return to course button text.'),
            '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.default.return_text'),
          ],
          'flashpoint_course_content__next_text' => [
            '#type' => 'textfield',
            '#title' => t('Next Button Text'),
            '#description' => t('The next button text.'),
            '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.default.next_text'),
          ],
        ],
        'default_renderer' => [
          '#type' => 'details',
          '#open' => TRUE,
          '#title' => t('Default Renderer Settings'),
          'flashpoint_course_content__default__renderer_listing' => [
            '#type' => 'select',
            '#title' => t('Default Listing Renderer'),
            '#description' => t('The default render plugin for all content, in the course/module listing context.'),
            '#empty_option' => t(' - Select - '),
            '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.default.renderer_listing'),
            '#options' => $plugin_options,
          ],
          'flashpoint_course_content__default__renderer_progress' => [
            '#type' => 'select',
            '#title' => t('Default Progress Button Renderer'),
            '#description' => t('The default render plugin for progress buttons that show as a part of course content navigation.'),
            '#empty_option' => t(' - Select - '),
            '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.default.renderer_progress'),
            '#options' => $plugin_options,
          ],
          'flashpoint_course_content__default__renderer_progress_classes' => [
            '#type' => 'textfield',
            '#title' => t('Default Progress Button Renderer Classes'),
            '#description' => t('The default classes for progress buttons that show as a part of course content navigation.'),
            '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.default.renderer_progress_classes'),
          ],
          'flashpoint_course_content__default__neutral_class' => [
            '#type' => 'textfield',
            '#title' => t('Neutral Status Class'),
            '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.default.neutral_class'),
          ],
          'flashpoint_course_content__default__neutral_icon' => [
            '#type' => 'textfield',
            '#title' => t('Neutral Icon'),
            '#description' => t('HTML of the neutral icon'),
            '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.default.neutral_icon'),
          ],
          'flashpoint_course_content__default__lock_class' => [
            '#type' => 'textfield',
            '#title' => t('Locked Status Class'),
            '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.default.lock_class'),
          ],
          'flashpoint_course_content__default__lock_icon' => [
            '#type' => 'textfield',
            '#title' => t('Lock Icon'),
            '#description' => t('HTML of the locked status icon'),
            '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.default.lock_icon'),
          ],
          'flashpoint_course_content__default__pending_class' => [
            '#type' => 'textfield',
            '#title' => t('Pending Status Class'),
            '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.default.pending_class'),
          ],
          'flashpoint_course_content__default__pending_icon' => [
            '#type' => 'textfield',
            '#title' => t('Pending Icon'),
            '#description' => t('HTML of the pending status icon'),
            '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.default.pending_icon'),
          ],
          'flashpoint_course_content__default__passed_class' => [
            '#type' => 'textfield',
            '#title' => t('Passed Status Class'),
            '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.default.passed_class'),
          ],
          'flashpoint_course_content__default__passed_icon' => [
            '#type' => 'textfield',
            '#title' => t('Passed Icon'),
            '#description' => t('HTML of the passed status icon'),
            '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.default.passed_icon'),
          ],
        ],
      ]
    ];

    $bundle_info = \Drupal::service('entity_type.bundle.info');
    $bundles = $bundle_info->getBundleInfo('flashpoint_course_content');
    foreach ($bundles as $key => $bundle) {
      $bundle_label = '';
      if ($bundle['label'] instanceof TranslatableMarkup) {
        $bundle_label = $bundle['label']->render();
      }
      else {
        $bundle_label = $bundle['label'];
      }
      $ret['course_content_render_options'][$key . '_renderer'] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => t($bundle_label . ' Settings'),
        'flashpoint_course_content__' . $key . '__renderer_listing' => [
          '#type' => 'select',
          '#title' => t('Renderer'),
          '#description' => t('The render plugin for all content, in the course/module listing context. If none is selected, the default is used.'),
          '#empty_option' => t(' - Select - '),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.' . $key . '.renderer_listing'),
          '#options' => $plugin_options,
        ],
        'flashpoint_course_content__' . $key . '__renderer_progress' => [
          '#type' => 'select',
          '#title' => t('Progress Button Renderer'),
          '#description' => t('The default render plugin for progress buttons that show as a part of course content navigation.'),
          '#empty_option' => t(' - Select - '),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.' . $key . '.renderer_progress'),
          '#options' => $plugin_options,
        ],
        'flashpoint_course_content__' . $key . '__neutral_class' => [
          '#type' => 'textfield',
          '#title' => t('Neutral Status Class'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.' . $key . '.neutral_class'),
        ],
        'flashpoint_course_content__' . $key . '__neutral_icon' => [
          '#type' => 'textfield',
          '#title' => t('Neutral Icon'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.' . $key . '.neutral_icon'),
        ],
        'flashpoint_course_content__' . $key . '__lock_class' => [
          '#type' => 'textfield',
          '#title' => t('Locked Status Class'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.' . $key . '.lock_class'),
        ],
        'flashpoint_course_content__' . $key . '__lock_icon' => [
          '#type' => 'textfield',
          '#title' => t('Lock Icon'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.' . $key . '.lock_icon'),
        ],
        'flashpoint_course_content__' . $key . '__pending_class' => [
          '#type' => 'textfield',
          '#title' => t('Pending Status Class'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.' . $key . '.pending_class'),
        ],
        'flashpoint_course_content__' . $key . '__pending_icon' => [
          '#type' => 'textfield',
          '#title' => t('Pending Icon'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.' . $key . '.pending_icon'),
        ],
        'flashpoint_course_content__' . $key . '__passed_class' => [
          '#type' => 'textfield',
          '#title' => t('Passed Status Class'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.' . $key . '.passed_class'),
        ],
        'flashpoint_course_content__' . $key . '__passed_icon' => [
          '#type' => 'textfield',
          '#title' => t('Passed Icon'),
          '#default_value' => $flashpoint_config->getOriginal('flashpoint_course_content.' . $key . '.passed_icon'),
        ],
      ];
    }
    return $ret;
  }

}