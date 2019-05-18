<?php

namespace Drupal\m200wad_api\Form;

use Drupal\Core\Form\FormStateInterface;

class ConfigForm extends ConfigFormBase {

  public function getFormId() {
    return 'm200wad_api_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->getConfig();

    $form['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('200WAD API Token'),
      '#default_value' => $config->get('token'),
      '#description' => $this->t('Your 200 Words a day API token.'),
    ];

    $form['base_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('200WAD API Base URL'),
      '#default_value' => $config->get('base_uri'),
      '#description' => $this->t('Include trailing slash.'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
