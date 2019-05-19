<?php

namespace Drupal\webex\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class WebexForm extends ConfigFormBase {

  public function getFormId() {
    return 'webex_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webex.settings');
    $form['webex_api_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('WebEx API Url'),
      '#required' => TRUE,
      '#description' => t("WebEx Api Url"),
      '#default_value' => $config->get('webex_api_url'),
    ];
    $form['webex_api_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('WebEx ID'),
      '#required' => TRUE,
      '#description' => t("WebEx Api ID"),
      '#default_value' => $config->get('webex_api_id'),
    ];
    $form['webex_api_pwd'] = [
      '#type' => 'textfield',
      '#title' => $this->t('WebEx Api Password'),
      '#required' => TRUE,
      '#description' => t("WebEx Api Password"),
      '#default_value' => $config->get('webex_api_pwd'),
    ];
    $form['webex_site_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('WebEx Site ID'),
      '#required' => TRUE,
      '#description' => t("WebEx Api Site ID"),
      '#default_value' => $config->get('webex_site_id'),
    ];
    $form['webex_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('WebEx Email'),
      '#required' => TRUE,
      '#description' => t("WebEx Api Email"),
      '#default_value' => $config->get('webex_email'),
    ];
    $form['webex_content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Content Type'),
      '#required' => TRUE,
      '#options' => node_type_get_names(), 
      '#description' => t("WebEx Content Type"),
      '#default_value' => $config->get('webex_content_type'),
    ];
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('webex.settings');
    $config->set('webex_api_url', $form_state->getValue('webex_api_url'));
    $config->set('webex_api_id', $form_state->getValue('webex_api_id'));
    $config->set('webex_api_pwd', $form_state->getValue('webex_api_pwd'));
    $config->set('webex_site_id', $form_state->getValue('webex_site_id'));
    $config->set('webex_email', $form_state->getValue('webex_email'));
    $config->set('webex_content_type', $form_state->getValue('webex_content_type'));
    $config->save();
    parent::submitForm($form, $form_state);
  }
  protected function getEditableConfigNames() {
    return ['webex.settings'];
  }

}
