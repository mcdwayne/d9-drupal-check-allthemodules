<?php

namespace Drupal\senhaunicausp\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SenhaunicauspForm.
 */
class SenhaunicauspForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'senhaunicausp.config',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'senhaunicausp_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('senhaunicausp.config');
    $form['key_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#description' => $this->t('Sua chave. Exemplo: fflch'),
      '#default_value' => $config->get('key_id'),
    ];
    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Key'),
      '#description' => $this->t(''),
      '#default_value' => $config->get('secret_key'),
    ];
    $form['callback_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Callback Id'),
      '#description' => $this->t('callback id'),
      '#default_value' => $config->get('callback_id'),
    ];
    $form['numeros_usp'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Números USP'),
      '#description' => $this->t('Números USP que podem logar neste site, separado por vírgula. Caso deixe em branco, qualquer pessoa poderá logar no site.'),
      '#default_value' => $config->get('numeros_usp'),
    ];
    $form['default_role'] = [
      '#type' => 'select',
      '#title' => $this->t('Role Padrão'),
      '#description' => $this->t('Role padrão para quem logar com senha única'),
      '#default_value' => $config->get('default_role'),
      '#options' => $this->getRolesNames(),
    ];
    $form['numeros_usp_service'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Também consumir lista de números USP de um serviço externo?'),
      '#description' => $this->t(''),
      '#default_value' => $config->get('numeros_usp_service'),
    ];
    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#description' => $this->t(''),
      '#default_value' => $config->get('endpoint'),
    ];
    $form['apikey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Api Key'),
      '#description' => $this->t(''),
      '#default_value' => $config->get('apikey'),
    ];
    return parent::buildForm($form, $form_state);
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
    parent::submitForm($form, $form_state);

    $this->config('senhaunicausp.config')
      ->set('key_id', $form_state->getValue('key_id'))
      ->set('secret_key', $form_state->getValue('secret_key'))
      ->set('callback_id', $form_state->getValue('callback_id'))
      ->set('numeros_usp', $form_state->getValue('numeros_usp'))
      ->set('default_role', $form_state->getValue('default_role'))
      ->set('numeros_usp_service', $form_state->getValue('numeros_usp_service'))
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->set('apikey', $form_state->getValue('apikey'))
      ->save();
  }

  public function getRolesNames() {
    $roles = \Drupal::entityTypeManager()->getStorage('user_role')->loadMultiple();
    $roles_name = [];
    foreach ($roles as $key => $value) {
        $roles_name[$key] = $key;
    }

    // Anonymous or authenticated role ID must not be assigned manually
    unset($roles_name['anonymous']);
    return $roles_name;
  }

}
