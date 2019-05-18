<?php

/**
 * @file
 * Contains \Drupal\communico\Form\CommunicoConfigForm.
 */

namespace Drupal\communico\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class CommunicoConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'communico_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $config = $this->config('communico.settings');

    $form['access_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Key'),
      '#default_value' => $config->get('access_key'),
      '#required' => TRUE,
    ];

    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Key'),
      '#default_value' => $config->get('secret_key'),
      '#required' => TRUE,
    ];

    $form['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Communico API URL'),
      '#default_value' => $config->get('url'),
      '#required' => TRUE,
    ];

    $form['linkurl'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Communico Public URL'),
      '#default_value' => $config->get('linkurl'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('communico.settings');

    $config->set('access_key', $form_state->getValue('access_key'));
    $config->set('secret_key', $form_state->getValue('secret_key'));
    $config->set('url', $form_state->getValue('url'));
    $config->set('linkurl', $form_state->getValue('linkurl'));

    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'communico.settings',
    ];
  }
}
