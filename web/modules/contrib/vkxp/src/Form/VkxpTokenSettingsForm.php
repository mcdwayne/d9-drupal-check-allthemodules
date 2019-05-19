<?php

/**
 * @file
 * Contains \Drupal\vkxp\Form\vkxpSettingsForm.
 */

namespace Drupal\vkxp\Form;


use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

class VkxpTokenSettingsForm extends ConfigFormBase {

  public function getFormId() {
    return 'vkxp_token_settings';
  }

  protected function getEditableConfigNames() {
    return [
      'vkxp.token_settings',
    ];
  }


  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('vkxp.token_settings');


    $form['vkxp_access_token'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Write a token from VK'),
      '#default_value' => $config->get('vkxp_access_token'),
    );

    // FIRST STEP.
    // Getting authorize code from VK.
    $params = array();
    //@TODO add app_id after main config form would be finished.
    //$params['client_id']     = variable_get('vkxp_app_id');
    $params['scope']         = VKXP_AUTHORIZE_SCOPE;
    $params['redirect_uri']  = VKXP_ACCESS_TOKEN_REDIRECT_URI;
    $params['response_type'] = VKXP_AUTHORIZE_RESPONSE_TYPE;
    $url = Url::fromUri(VKXP_AUTHORIZE_URI, array('query' => $params, 'target' => '_blank'));
    $external_link = \Drupal::l(t('Get code'), $url);
    $form['link'] = array(
      '#markup' => $external_link
    );


    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('vkxp.token_settings')
      ->set('vkxp_access_token', $form_state->getValue('vkxp_access_token'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
