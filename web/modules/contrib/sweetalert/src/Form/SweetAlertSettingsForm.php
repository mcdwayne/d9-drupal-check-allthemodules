<?php

/**
 * @file
 * Contains Drupal\sweetalert\Form\SweetAlertSettingsForm.
 */

namespace Drupal\sweetalert\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SweetAlertSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sweetalert_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['sweetalert.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sweetalert.settings');

    $form['sweetalert'] = array(
      '#type' => 'details',
      '#title' => $this->t('SweetAlert Settings'),
      '#open' => true,
      '#description' => $this->t('Adjust default settings for SweetAlert.')
    );

    $form['sweetalert']['theme'] = [
      '#type' => 'select',
      '#title' => 'Theme',
      '#description' => $this->t('Select a theme to apply to SweetAlert. Below are options that come with SweetAlert - you can define your own and include them from your module or theme.'),
      '#default_value' => $config->get('theme'),
      '#options' => [
        '' => '- Select a Theme -',
        'facebook' => 'Facebook',
        'google' => 'Google',
        'twitter' => 'Twitter'
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('sweetalert.settings')
      ->set('theme', $form_state->getValue('theme'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}