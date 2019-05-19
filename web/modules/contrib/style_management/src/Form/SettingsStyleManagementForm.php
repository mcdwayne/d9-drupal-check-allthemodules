<?php

namespace Drupal\style_management\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsStyleManagementForm.
 */
class SettingsStyleManagementForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'style_management.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_style_management_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('style_management.settings');

    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#open' => TRUE,
    ];

    $form['general']['enable_watcher'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Watcher'),
      '#default_value' => $config->get('setting.enable_watcher'),
    ];
    $form['build'] = [
      '#type' => 'details',
      '#title' => $this->t('rebuild Style at'),
      '#open' => TRUE,
    ];

    $form['build']['build_hook_css_alter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('hook_css_alter()'),
      '#default_value' => $config->get('setting.build_hook_css_alter'),
    ];

    $form['build']['build_hook_cache_flush'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('hook_cache_flush()'),
      '#default_value' => $config->get('setting.build_hook_cache_flush'),
    ];

    $form['build']['build_hook_preprocess_page'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('hook_preprocess_page()'),
      '#default_value' => $config->get('setting.build_hook_preprocess_page'),
    ];

    $form['less'] = [
      '#type' => 'details',
      '#title' => $this->t('LESS'),
      '#open' => TRUE,
    ];

    // Fields Less.
    $form['less']['less_compress'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Compress'),
      '#default_value' => $config->get('setting.less_compress'),
    ];
    $form['less']['less_cache_folder'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cache Folder'),
      '#default_value' => $config->get('setting.less_cache_folder'),
    ];
    $form['less']['less_cache_folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cache Folder'),
      '#default_value' => $config->get('setting.less_cache_folder'),
    ];
    $form['less']['less_default_destination_folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Destination Compiled Folder'),
      '#default_value' => $config->get('setting.less_default_destination_folder'),
    ];

    $form['scss'] = [
      '#type' => 'details',
      '#title' => $this->t('SCSS'),
      '#open' => TRUE,
    ];

    // Fields Scss.
    $form['scss']['scss_compress'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Compress'),
      '#default_value' => $config->get('setting.scss_compress'),
    ];
    $form['scss']['scss_cache_folder'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Cache Folder'),
      '#default_value' => $config->get('setting.scss_cache_folder'),
    ];
    $form['scss']['scss_cache_folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cache Folder'),
      '#default_value' => $config->get('setting.scss_cache_folder'),
    ];
    $form['scss']['scss_default_destination_folder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default Destination Compiled Folder'),
      '#default_value' => $config->get('setting.scss_default_destination_folder'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('style_management.settings')
      ->set('setting.enable_watcher', $form_state->getValue('enable_watcher'))
      ->set('setting.build_hook_css_alter', $form_state->getValue('build_hook_css_alter'))
      ->set('setting.build_hook_cache_flush', $form_state->getValue('build_hook_cache_flush'))
      ->set('setting.build_hook_preprocess_page', $form_state->getValue('build_hook_preprocess_page'))
      ->set('setting.less_compress', $form_state->getValue('less_compress'))
      ->set('setting.less_cache_folder', $form_state->getValue('less_cache_folder'))
      ->set('setting.less_default_destination_folder', $form_state->getValue('less_default_destination_folder'))

      ->set('setting.scss_compress', $form_state->getValue('scss_compress'))
      ->set('setting.scss_cache_folder', $form_state->getValue('scss_cache_folder'))
      ->set('setting.scss_default_destination_folder', $form_state->getValue('scss_default_destination_folder'))
      ->save();
  }

}
