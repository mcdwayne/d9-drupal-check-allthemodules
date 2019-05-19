<?php

namespace Drupal\steam_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\steam_api\Data;

/**
 * Module settings form.
 */
class ModuleSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'steam_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'steam_api.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $options = [
      'attributes' => [
        'target' => '_blank',
      ],
    ];
    $getapikeyurl = Url::fromUri(Data::STEAM_GET_API_KEY_URL, $options);

    $form['steam_apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Steam API Key"),
      '#description' => $this->t(
        'You can get your Steam API Key here: <a href="@getapikeyurl" target="_blank">@getapikeyurl</a>',
        [
          '@getapikeyurl' => $getapikeyurl->toString(),
        ]
      ),
      '#default_value' => $this->configFactory->getEditable('steam_api.settings')
        ->get('steam_apikey'),
      '#required' => TRUE,
    ];

    return $form;
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
    $this->configFactory->getEditable('steam_api.settings')
      ->set('steam_apikey', $form_state->getValue('steam_apikey'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
