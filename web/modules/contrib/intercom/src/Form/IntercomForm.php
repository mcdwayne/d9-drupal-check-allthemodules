<?php

namespace Drupal\intercom\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class IntercomForm.
 *
 * @package Drupal\intercom\Form
 */
class IntercomForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'intercom.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'intercom_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('intercom.settings');

    $form['intercom_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Mode'),
      '#description' => $this->t('Whether intercom operates in test or production mode.'),
      '#default_value' => $config->get('intercom_mode'),
      '#options' => [0 => $this->t('Test'), 1 => $this->t('Production')],
    ];

    $form['intercom_test_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Intercom test App ID'),
      '#description' => $this->t('App ID of your intercom test app.'),
      '#default_value' => $config->get('intercom_test_app_id'),
      '#states' => [
        'visible' => [
          ':input[name="intercom_mode"]' => ['value' => 0],
        ],
      ],
    ];

    $form['intercom_live_app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Intercom production App ID'),
      '#description' => $this->t('App ID of your intercom production app.'),
      '#default_value' => $config->get('intercom_live_app_id'),
      '#states' => [
        'visible' => [
          ':input[name="intercom_mode"]' => ['value' => 1],
        ],
      ],
    ];

    $form['intercom_an_only'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show intercom to anonymous users only'),
      '#description' => $this->t('When checked logged in user will not be bothered by intercom.'),
      '#default_value' => $config->get('intercom_an_only'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('intercom.settings')
      ->set('intercom_mode', $form_state->getValue('intercom_mode'))
      ->set('intercom_test_app_id', $form_state->getValue('intercom_test_app_id'))
      ->set('intercom_live_app_id', $form_state->getValue('intercom_live_app_id'))
      ->set('intercom_an_only', $form_state->getValue('intercom_an_only'))
      ->save();
  }

}
