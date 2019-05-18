<?php

namespace Drupal\redirecton403\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Redirect403AdminSettings.
 */
class Redirect403AdminSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'redirecton403.redirect403adminsettings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redirect403_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('redirecton403.redirect403adminsettings');
    $form['redirect_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Redirect Settings'),
      '#description' => $this->t('Used to set the redirection'),
    ];
    $form['redirect_settings']['url_item_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Login Type'),
      '#description' => $this->t('Opt Login Type.'),
      '#options' => ['opt_internal' => 'Internal', 'opt_external' => 'External'],
      '#default_value' => $config->get('url_item_type'),
    ];
    $form['redirect_settings']['internal_route'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Internal Route'),
      '#description' => $this->t('Internal Route Name to be provieded'),
      '#maxlength' => 64,
      '#size' => 30,
      '#default_value' => $config->get('internal_route'),
    ];

    $form['redirect_settings']['internal_route']['#states'] = [
      'visible' => [
        [
          [':input[name="url_item_type"]' => ['value' => 'opt_internal']],
        ],
      ],
    ];
    $form['redirect_settings']['external_url'] = [
      '#type' => 'textarea',
      '#title' => $this->t('External URL'),
      '#description' => $this->t('Please provide the external URL for login services.'),
      '#default_value' => $config->get('external_url'),
    ];
    $form['redirect_settings']['external_url']['#states'] = [
      'visible' => [
        [
          [':input[name="url_item_type"]' => ['value' => 'opt_external']],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('redirecton403.redirect403adminsettings')
      ->set('url_item_type', $form_state->getValue('url_item_type'))
      ->set('internal_route', $form_state->getValue('internal_route'))
      ->set('external_url', $form_state->getValue('external_url'))
      ->save();
  }

}
