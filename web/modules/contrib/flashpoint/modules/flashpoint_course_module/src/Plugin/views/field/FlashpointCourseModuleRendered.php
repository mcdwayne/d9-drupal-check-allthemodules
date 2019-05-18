<?php


namespace Drupal\flashpoint_course_module\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\flashpoint_course_content\Entity\FlashpointCourseContent;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Defines a views field plugin.
 *
 * @ingroup views_area_handlers
 *
 * @ViewsField("flashpoint_course_module_rendered")
 */

class FlashpointCourseModuleRendered extends FieldPluginBase {
  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }
  /**
   * Define the available options
   * @return array
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['module_text'] = ['default' => ''];
    $options['neutral_class'] = ['default' => ''];
    $options['locked_class'] = ['default' => ''];
    $options['pass_class'] = ['default' => ''];
    $options['pending_class'] = ['default' => ''];
    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['neutral_class'] = [
      '#type' => 'textfield',
      '#title' => t('Module Class for neutral status'),
      '#description' => t('If Flashpoint LRS is not enabled, a module will be rendered with a neutral status.'),
      '#default_value' => $this->options['neutral_class'],
      '#weight' => -1,
    ];
    $form['locked_class'] = [
      '#type' => 'textfield',
      '#title' => t('Module Class for locked status'),
      '#description' => t('If a module is inacessible, its will show with a "lock" status.'),
      '#default_value' => $this->options['locked_class'],
      '#weight' => -1,
    ];
    $form['pass_class'] = [
      '#type' => 'textfield',
      '#title' => t('Module Class for pass status'),
      '#description' => t('If LRS is present, passed course module will be rendered with a pass status.'),
      '#default_value' => $this->options['pass_class'],
      '#weight' => -1,
    ];
    $form['pending_class'] = [
      '#type' => 'textfield',
      '#title' => t('Module Class for pending status'),
      '#description' => t('If LRS is present, non-passed course module will be rendered with a pending status.'),
      '#default_value' => $this->options['pending_class'],
      '#weight' => -1,
    ];
  }
  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $data) {
    $neutral_class = $this->options['neutral_class'];
    $locked_class = $this->options['locked_class'];
    $pass_class = $this->options['pass_class'];
    $pending_class = $this->options['pending_class'];
    $cm = $this->getEntity($data);
    $user = \Drupal::currentUser();

    $neutral_status = $cm->isNeutral($user);


    $module_class = $neutral_class;
    $lock_status = $cm->isLocked($user);
    // If the item is locked, we want to close it.
    if (!$neutral_status) {
      $pass_status = $cm->isPassed($user);
      $module_class = $pass_status ? $pass_class : $pending_class;
      $module_class = $lock_status ? $locked_class : $module_class;
    }

    $course_module = [
      '#type' => 'details',
      '#open' => !$lock_status,
      '#title' => $cm->label(),
      '#title_attributes' => [
        'class' => [$module_class],
      ],
    ];

    $course_module['instructional'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
    ];

    $course_module['examination'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
    ];

    // Instructional and Examination Content
    $flashpoint_config = \Drupal::configFactory()->getEditable('flashpoint.settings');
    $content_settings = $flashpoint_config->getOriginal('flashpoint_course_content');
    $default_renderer = isset($content_settings['default']['renderer_listing']) && !empty($content_settings['default']['renderer_listing']) ?
      $content_settings['default']['renderer_listing']: 'flashpoint_course_content_default_renderer';
    $plugin_manager = \Drupal::service('plugin.manager.flashpoint_course_content_renderer');
    $plugin_definitions = $plugin_manager->getDefinitions();

    foreach(['instructional', 'examination'] as $type) {
      $c = $cm->get('field_' . $type . '_content')->getValue();
      foreach ($c as $item) {
        $content = FlashpointCourseContent::load($item['target_id']);
        $bundle = $content->bundle();
        $renderer = isset($content_settings[$bundle]['renderer_listing']) && !empty($content_settings[$bundle]['renderer_listing']) ?
          $content_settings[$bundle]['renderer_listing'] : $default_renderer;
        $rendered = $plugin_definitions[$renderer]['class']::renderListing($content, $user);
        $course_module[$type]['content_' . $item['target_id']] = $rendered;
      }
    }

    return $course_module;
  }
}