<?php

namespace Drupal\pubg_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\pubg_api\Data;

/**
 * Module settings form.
 */
class ModuleSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pubg_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'pubg_api.settings',
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
    $getapikeyurl = Url::fromUri(Data::PUBG_GET_API_KEY_URL, $options);

    $form['pubg_apikey'] = [
      '#type' => 'textarea',
      '#title' => $this->t("PUBG API Key"),
      '#description' => $this->t(
        'You can get your PUBG API Key here: <a href="@getapikeyurl" target="_blank">@getapikeyurl</a>',
        [
          '@getapikeyurl' => $getapikeyurl->toString(),
        ]
      ),
      '#default_value' => $this->configFactory->getEditable('pubg_api.settings')
        ->get('pubg_apikey'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('pubg_api.settings')
      ->set('pubg_apikey', $form_state->getValue('pubg_apikey'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
