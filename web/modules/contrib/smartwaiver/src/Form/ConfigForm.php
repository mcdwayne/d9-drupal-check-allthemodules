<?php

namespace Drupal\smartwaiver\Form;

use Drupal\Core\Form\FormStateInterface;

class ConfigForm extends ConfigFormBase {

  public function getFormId() {
    return 'smartwaiver_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfig();

    $form['api_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Smartwaiver API Key'),
      '#default_value' => $config->get('api_key'),
    ];

    $form['webhook_key'] = [
      '#type' => 'key_select',
      '#title' => $this->t('Smartwaiver Webhook Private Key'),
      '#default_value' => $config->get('webhook_key'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
