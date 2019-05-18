<?php
namespace Drupal\bg_rotate\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\file\Entity\File;

/**
 * Image rotate file upload form.
 */
class BgRotateSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bg_rotate_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return [
      'bg_rotate.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Get config.
    $state = \Drupal::state();
    $config = $this->config('bg_rotate.settings');
    // Multiple file upload.
    $form['images'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Files'),
      '#description' => $this->t('Select background images.'),
      '#default_value' => $state->get('bg_rotate.images'),
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#progress_indicator' => 'bar',
      '#upload_location' => 'public://images/bg_rotate/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpg jpeg gif'],
        'file_validate_size' => [10000000],
      ],
    ];

    $form['selector'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CSS selector'),
      '#description' => $this->t('Enter css selector here, default will be body'),
      '#default_value' => $config->get('selector') ? $config->get('selector') : 'body',
    ];

    $form['interval'] = [
      '#type' => 'select',
      '#title' => $this->t('Time interval'),
      '#description' => $this->t('Time between background switching.'),
      '#options' => [
        'month' => $this->t('Month'),
        'week' => $this->t('Week'),
        'day' => $this->t('Day'),
        'hour' => $this->t('Hour'),
        'minute' => $this->t('Minute'),
        'second' => $this->t('Second'),
      ],
      '#default_value' => $config->get('interval'),
    ];

    $form['show_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show on admin pages'),
      '#default_value' => $config->get('show_admin'),
    ];

    // Get image styles.
    $image_styles = image_style_options(FALSE);

    // Set a none option to image styles.
    $image_styles['raw'] = $this->t('Original image');
    $image_styles['none'] = $this->t('None');

    // Breakpoints.
    $form['breakpoints'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Breakpoints'),
      '#prefix' => '<div id="breakpoints-wrapper">',
      '#suffix' => '</div>',
    ];

    $i = 0;

    $breakpoints_config = $config->get('breakpoints');
    $count = $form_state->get('num_breakpoints');
    if (!$count) {
      // Get count for default values.
      $count = !empty($breakpoints_config) ? count($breakpoints_config) : 1;
      $form_state->set('num_breakpoints', $count);
    }

    for ($i = 0; $i < $count; $i++) {
      $form['breakpoints']['breakpoint'][$i] = [
        '#type' => 'fieldset',
      ];

      $form['breakpoints']['breakpoint'][$i]['width'] = [
        '#type' => 'number',
        '#title' => $this->t('Width'),
        '#default_value' => $breakpoints_config[$i]['width'],
        '#description' => $this->t('Width in pixels since we are using javascript'),
      ];

      $form['breakpoints']['breakpoint'][$i]['image_style'] = [
        '#type' => 'select',
        '#title' => $this->t('Image style'),
        '#options' => $image_styles,
        '#default_value' => $breakpoints_config[$i]['image_style'],
      ];
    }

    $form['breakpoints']['actions']['add_breakpoint'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add breakpoint'),
      '#submit' => ['::addBreakpoint'],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'breakpoints-wrapper',
      ],
    ];

    if ($count > 1) {
      $form['breakpoints']['actions']['remove_breakpoint'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove one'),
        '#submit' => ['::removeCallback'],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'breakpoints-wrapper',
        ],
      ];
    }

    // CSS settings.
    $form['bg_style'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Background style'),
    ];

    $form['bg_style']['background_repeat'] = [
      '#type' => 'select',
      '#title' => $this->t('Background repeat'),
      '#options' => [
        'no-repeat' => $this->t('No repeat'),
        'repeat' => $this->t('Repeat'),
        'repeat-x' => $this->t('Horizontal repeat'),
        'repeat-y' => $this->t('Vertical repeat'),
      ],
      '#default_value' => $config->get('background_repeat'),
    ];

    $form['bg_style']['background_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Background position'),
      '#options' => [
        'none' => $this->t('Top left'),
        'center' => $this->t('Centered'),
      ],
      '#default_value' => $config->get('background_position'),
    ];

    $form['bg_style']['background_attachment'] = [
      '#type' => 'select',
      '#title' => $this->t('Background attachment'),
      '#options' => [
        'scroll' => $this->t('Scroll'),
        'fixed' => $this->t('Fixed'),
      ],
      '#default_value' => $config->get('background_attachment'),
    ];

    $form['bg_style']['background_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Background size'),
      '#options' => [
        'auto' => $this->t('Normal'),
        'contain' => $this->t('Fit image'),
        'cover' => $this->t('Fill screen'),
      ],
      '#default_value' => $config->get('background_size'),
    ];

    // Submit.
    $form['actions']['#type'] = 'container';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    // Since we have multiple fields we need to use tree to access them.
    $form['breakpoints']['#tree'] = TRUE;
    $form_state->setCached(FALSE);
    return parent::buildForm($form, $form_state);
  }

  /**
   * Add a brakpoint callback.
   */
  public function addBreakpoint(array &$form, FormStateInterface $form_state) {
    $breakpoint_field = $form_state->get('num_breakpoints');
    $add_button = $breakpoint_field + 1;
    $form_state->set('num_breakpoints', $add_button);
    $form_state->setRebuild();
  }

  /**
   * Add item callback.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    $breakpoint_field = $form_state->get('num_breakpoints');
    return $form['breakpoints'];
  }

  /**
   * Remove item callback.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $breakpoint_field = $form_state->get('num_breakpoints');
    if ($breakpoint_field > 1) {
      $remove_button = $breakpoint_field - 1;
      $form_state->set('num_breakpoints', $remove_button);
    }
    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $state = \Drupal::state();
    $config = $this->config('bg_rotate.settings');
    $file_usage = \Drupal::service('file.usage');
    // Check removed files and delete.
    if ($state->get('bg_rotate.images')) {
      $diff = array_diff($state->get('bg_rotate.images'), $form_state->getValue('images'));
      if (!empty($diff)) {
        $files = File::loadMultiple($diff);
        foreach ($files as $file) {
          $file_usage->delete($file, 'bg_rotate', 'file', $file->id());
        }
      }
    }
    // Set states.
    $state->set('bg_rotate.images', $form_state->getValue('images'));
    $breakpoints = $form_state->getValue('breakpoints');
    // Save configuration.
    $this->config('bg_rotate.settings')
      ->set('selector', $form_state->getValue('selector'))
      ->set('interval', $form_state->getValue('interval'))
      ->set('show_admin', $form_state->getValue('show_admin'))
      ->set('breakpoints', $breakpoints['breakpoint'])
      ->set('background_repeat', $form_state->getValue('background_repeat'))
      ->set('background_position', $form_state->getValue('background_position'))
      ->set('background_attachment', $form_state->getValue('background_attachment'))
      ->set('background_size', $form_state->getValue('background_size'))
      ->save();
    // Make files permanent.
    $files = file_load_multiple($state->get('bg_rotate.images'));
    foreach ($files as $file) {
      // Check if file has usage and add if needed.
      if (!$file_usage->listUsage($file)) {
        $file_usage->add($file, 'bg_rotate', 'file', $file->id());
      }
    }
    drupal_set_message($this->t('Settings saved.'));
  }

}
