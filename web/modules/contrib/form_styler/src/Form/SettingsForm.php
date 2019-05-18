<?php

/**
 * @file
 * Contains \Drupal\form_styler\Form\SettingsForm.
 */

namespace Drupal\form_styler\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

#use Drupal\Core\Url;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'form_styler.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_styler_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('form_styler.settings');
    $form['form_ids'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Form ids'),
      '#description' => $this->t('Specify the id of form for which you want to use form styler'),
      '#default_value' => $config->get('form_ids'),
    ];
    $theme_list = \Drupal::service('theme_handler')->listInfo();
    $options = [];
    foreach ($theme_list as $theme_name => $theme) {
      $options[$theme_name] = $theme->info['name'];
    }
    $selected_themes = $config->get('themes_enabled');
    $form['themes'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Select themes'),
      '#description' => $this->t('Enable for all forms on selected themes'),
      '#options' => $options,
      '#default_value' => $selected_themes ? $selected_themes : [],
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('form_styler.settings')
        ->set('form_ids', $values['form_ids'])
        ->set('themes_enabled', $values['themes'])
        ->save();
   parent::submitForm($form, $form_state);
  }

}
