<?php

namespace Drupal\extensions_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Configure example settings for this site.
 */
class ExtensionsApiSettingsForm extends ConfigFormBase {
  
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'extensions_api_admin_settings';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'extensions_api.settings',
    ];
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('extensions_api.settings');
    $token = $config->get('token', NULL);
    if (!empty($token)) {
      $link = Link::fromTextAndUrl(
          t('Link to the API'),
          Url::fromRoute('extensions_api.showList', [
            'category' => 'all',
            'token' => $token,
          ])
        )->toString();
      $form['intro'] = [
        '#type' => 'markup',
        '#markup' => $link,
      ];
    }
    $form['extensions_api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access token'),
      '#default_value' => $token,
    ];
    return parent::buildForm($form, $form_state);
  }

  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('extensions_api.settings')
      ->set('token', $form_state->getValue('extensions_api_token'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
