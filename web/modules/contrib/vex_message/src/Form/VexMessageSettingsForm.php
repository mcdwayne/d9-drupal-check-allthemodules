<?php

namespace Drupal\vex_message\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class VexMessageSettingsForm.
 *
 * @package Drupal\vex_message\Form
 */
class VexMessageSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vex_message_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vex_message.settings');

    $form['vex_message_status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Enable Vex Message'),
      '#default_value' => $config->get('status') ? $config->get('status') : 0,
      '#options' => [
        1 => $this->t('Enabled'),
        0 => $this->t('Disabled'),
      ],
    ];

    $form['container'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Vex Message settings'),
      '#collapsed' => FALSE,
      '#collapsible' => TRUE,
    ];
    $form['container']['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Select theme'),
      '#default_value' => $config->get('theme'),
      '#options' => [
        'vex-theme-default' => $this->t('Default'),
        'vex-theme-os' => $this->t('Operating System'),
        'vex-theme-plain' => $this->t('Plain'),
        'vex-theme-wireframe' => $this->t('Wireframe'),
        'vex-theme-flat-attack' => $this->t('Flat Attack!'),
        'vex-theme-top' => $this->t('Top'),
        'vex-theme-bottom-right-corner' => $this->t('Bottom Right Corner'),
      ],
    ];
    $form['container']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Message title'),
      '#default_value' => $config->get('title'),
    ];

    $vex_message_body = $config->get('body');

    $form['container']['body'] = [
      '#type' => 'text_format',
      '#base_type' => 'textarea',
      '#title' => $this->t('Message body'),
      '#default_value' => $vex_message_body['value'],
      '#format' => isset($vex_message_body['format']) ? $vex_message_body['format'] : NULL,
    ];

    $form['container']['cookie'] = [
      '#type' => 'radios',
      '#title' => $this->t('Check cookie'),
      '#description' => $this->t('Enabled message will be displayed until user close it once per browser session'),
      '#default_value' => $config->get('cookie') ? $config->get('cookie') : 0,
      '#options' => [
        1 => $this->t('Enabled'),
        0 => $this->t('Disabled'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vex_message.settings');

    $config->set('status', $form_state->getValue('vex_message_status'))
      ->set('title', $form_state->getValue('title'))
      ->set('body', $form_state->getValue('body'))
      ->set('theme', $form_state->getValue('theme'))
      ->set('cookie', $form_state->getValue('cookie'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vex_message.settings',
    ];
  }

}
