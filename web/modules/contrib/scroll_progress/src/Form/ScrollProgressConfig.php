<?php

namespace Drupal\scroll_progress\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Use this class to create configuration form for module.
 */
class ScrollProgressConfig extends ConfigFormBase {

  /**
   * Widget Id.
   */
  public function getFormId() {
    return 'scroll_progress_config';
  }

  /**
   * Create configurations Name.
   */
  protected function getEditableConfigNames() {
    return [
      'scroll_progress_config.settings',
    ];
  }

  /**
   * Create form for configurations.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('scroll_progress_config.settings');
    $form['scroll_progress_theme'] = [
      '#title' => $this->t('Select theme for Scroll to use'),
      '#description' => $this->t('Scroll comes with a lot of themes for progress. Please select the one that you prefer.'),
      '#type' => 'radios',
      '#options' => [
        '1' => $this->t('Straight line'),
        '2' => $this->t('Circular progress'),
        '3' => $this->t('Animated progress'),
        '4' => $this->t('Tooltip progress'),
        '5' => $this->t('Bottom line'),
      ],
      '#default_value' => $config->get('scroll_progress_theme') ? $config->get('scroll_progress_theme') : 1,
    ];

    $form['scroll_progress_color'] = [
      '#title' => $this->t('Color code.'),
      '#description' => $this->t('Default color for scroll progress is #ff0000.'),
      '#type' => 'textfield',
      '#default_value' => $config->get('scroll_progress_color') ? $config->get('scroll_progress_color') : '#ff0000',
    ];

    $form['scroll_progress_load_on_admin_enabled'] = [
      '#title' => $this->t('Load in administration pages.'),
      '#description' => $this->t('SCROLL is disabled by default on administration pages. Check to enable'),
      '#type' => 'checkbox',
      '#default_value' => $config->get('scroll_progress_load_on_admin_enabled') ? $config->get('scroll_progress_load_on_admin_enabled') : 0,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit popup after login configurations.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('scroll_progress_config.settings')
      ->set('scroll_progress_theme', $form_state->getValue('scroll_progress_theme'))
      ->set('scroll_progress_color', $form_state->getValue('scroll_progress_color'))
      ->set('scroll_progress_load_on_admin_enabled', $form_state->getValue('scroll_progress_load_on_admin_enabled'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
