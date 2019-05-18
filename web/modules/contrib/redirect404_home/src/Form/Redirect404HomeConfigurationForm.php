<?php

namespace Drupal\redirect404_home\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class Redirect404HomeConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'redirect404_home_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'redirect404_home.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('redirect404_home.settings');
    $form['redirection'] = [
      '#type' => 'select',
      '#title' => $this->t("Redirect method"),
      '#options' => [
        '301' => '301',
        '302' => '302',
        '303' => '303',
        '307' => '307',
      ],
      '#default_value' => $config->get('redirection'),
    ];
    $form['status_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Status message'),
      '#default_value' => $config->get('status_message'),
    ];
    $form['status_message_color'] = [
      '#type' => 'select',
      '#title' => $this->t("Status message color"),
      '#options' => [
        'error' => 'Red',
        'status' => 'Green',
        'warning' => 'Yellow',
      ],
      '#default_value' => $config->get('status_message_color'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('redirect404_home.settings')
      ->set('redirection', $values['redirection'])
      ->set('status_message', trim($values['status_message']))
      ->set('status_message_color', $values['status_message_color'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
