<?php

/**
 * @file
 * Configuration Setttings form for Perfect Scrollbar.
 */

namespace Drupal\perfect_scrollbar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'perfect_scrollbar_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'perfect_scrollbar.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default_config = \Drupal::config('perfect_scrollbar.settings');
    return array(
      'perfect_scrollbar' => $default_config->get('perfect_scrollbar.settings'),
      'perfect_scrollbar_settings' => $default_config->get('perfect_scrollbar.settings'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('perfect_scrollbar.settings');

    $form['perfect_scrollbar'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable Perfect Scrollbar functionality for this site.'),
      '#default_value' => $config->get('perfect_scrollbar'),
    );

    $form['perfect_scrollbar_settings'] = array(
      '#type' => 'textarea',
      '#title' => t('Custom scrollbar settings for every individual scroll in the site.'),
      '#default_value' => $config->get('perfect_scrollbar_settings'),
      '#description' => t('Follow pattern for setting custom attributes for a scrollbar. E.g. {class:perfect_scroll|height:200|width:300},{id:perfect_scroll|height:200|width:300}'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('perfect_scrollbar.settings');
     $config->set('perfect_scrollbar', $form_state->getValue('perfect_scrollbar'));
    $config->set('perfect_scrollbar_settings', $form_state->getValue('perfect_scrollbar_settings'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
