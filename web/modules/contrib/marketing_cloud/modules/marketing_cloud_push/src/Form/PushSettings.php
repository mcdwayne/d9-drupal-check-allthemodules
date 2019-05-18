<?php

namespace Drupal\marketing_cloud_push\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure custom_rest settings for this site.
 */
class PushSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'marketing_cloud_push_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['marketing_cloud_push.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('marketing_cloud_push.settings');

    $form['definitions'] = [
      '#type' => 'details',
      '#title' => $this->t('Endpoint definitions'),
      '#description' => $this->t('These are the endpoint definitions for Mobile Connect. <b>Important: Do not edit these unless you absolutely have to</b>.'),
      '#open' => FALSE,
      '#tree' => TRUE,
    ];
    foreach ($config->get('definitions') as $key => $value) {
      $form['definitions'][$key] = [
        '#type' => 'details',
        '#title' => $key,
        '#open' => FALSE,
        '#tree' => TRUE,
      ];
      $form['definitions'][$key]['method'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Method'),
        '#attributes' => ['placeholder' => $this->t('Please enter delivery method for %endpoint endpoint', ['%endpoint' => $key])],
        '#default_value' => $value['method'],
      ];
      $form['definitions'][$key]['endpoint'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Endpoint'),
        '#attributes' => ['placeholder' => $this->t('Please enter endpoint for %endpoint endpoint', ['%endpoint' => $key])],
        '#default_value' => $value['endpoint'],
      ];
      $form['definitions'][$key]['schema'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Schema'),
        '#attributes' => ['placeholder' => $this->t('Please enter schema for %endpoint endpoint', ['%endpoint' => $key])],
        '#default_value' => $value['schema'],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('marketing_cloud_push.settings');
    $definitions = $config->get('definitions');
    foreach ($definitions as $key => $value) {
      $config->set(
        "definitions.$key.method",
        $form_state->getValue(['definitions', $key, 'method'])
      );
      $config->set(
        "definitions.$key.endpoint",
        $form_state->getValue(['definitions', $key, 'endpoint'])
      );
      $config->set(
        "definitions.$key.schema",
        $form_state->getValue(['definitions', $key, 'schema'])
      );
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
