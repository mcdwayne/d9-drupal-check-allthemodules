<?php

namespace Drupal\sparkpost_requeue\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SettingsForm.
 *
 * @package Drupal\sparkpost_requeue\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sparkpost_requeue.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('sparkpost_requeue.settings');
    $form['enable'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable sparkpost requeue'),
      '#default_value' => $config->get('enable'),
    ];
    $form['max_retries'] = [
      '#type' => 'number',
      '#title' => $this->t('Max number of retries of a message'),
      '#default_value' => $config->get('max_retries'),
      '#states' => [
        'invisible' => [
          ':input[name="enable"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];
    $form['minimum_time'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum time between retries in seconds'),
      '#default_value' => $config->get('minimum_time'),
      '#states' => [
        'invisible' => [
          ':input[name="enable"]' => [
            'checked' => FALSE,
          ],
        ],
      ],
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('sparkpost_requeue.settings')
      ->set('enable', $form_state->getValue('enable'))
      ->set('max_retries', $form_state->getValue('max_retries'))
      ->set('minimum_time', $form_state->getValue('minimum_time'))
      ->save();
  }

}
