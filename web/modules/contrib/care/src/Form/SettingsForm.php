<?php

namespace Drupal\care\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use \SoapClient;
use \Exception;

/**
 * Provides a test form object.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'care_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('care.settings');

    $form['care_wsdl_url'] = array(
      '#title' => t('CARE WSDL URL'),
      '#type' => 'textfield',
      '#description' => t('Use the button below to test the URL without saving it.'),
      '#length' => 50,
      '#default_value' => $config->get('care_wsdl_url'),
    );

    $form['test_wsdl'] = array(
      '#value' => t('Test WDSL URL'),
      '#type' => 'submit',
      '#submit' => array(
        '::testWsdl',
      ),
    );

    $form['care_doc_root'] = array(
      '#title' => t('CARE documentation URL'),
      '#type' => 'textfield',
      '#description' => t('Home page for CARE API documentation.'),
      '#length' => 50,
      '#default_value' => $config->get('care_doc_root'),
    );

    $form['logging'] = array(
      '#title' => 'Logging Options',
      '#type' => 'fieldset',
    );

    $form['logging']['care_log_calls'] = array(
      '#title' => 'Log calls to CARE',
      '#type' => 'radios',
      '#options' => array(
        1 => 'Yes',
        0 => 'No',
      ),
      '#default_value' => $config->get('care_log_calls'),
    );

    $form['logging']['care_log_results'] = array(
      '#title' => 'Log results from CARE',
      '#type' => 'radios',
      '#options' => array(
        1 => 'Yes',
        0 => 'No',
      ),
      '#default_value' => $config->get('care_log_results'),
    );

    $form['actions'] = array(
      '#type' => 'actions',
    );
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (strlen($form_state->getValue('care_wsdl_url')) == 0) {
      $form_state->setErrorByName('care_wsdl_url', $this->t("Please enter a WSDL URL for CARE."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $this->config('care.settings')->set('care_wsdl_url', $form_state->getValue('care_wsdl_url'))->save();
    $this->config('care.settings')->set('care_doc_root', $form_state->getValue('care_doc_root'))->save();
    $this->config('care.settings')->set('care_log_calls', $form_state->getValue('care_log_calls'))->save();
    $this->config('care.settings')->set('care_log_results', $form_state->getValue('care_log_results'))->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array(
      'care.settings',
    );
  }

  /**
   * Test that the supplied WSDL URL works for SoapClient.
   */
  public function testWsdl(array &$form, FormStateInterface $form_state) {
    $url = $form_state->getValue('care_wsdl_url');
    try {
      $client = @new SoapClient($url);
      drupal_set_message(t('CARE WSDL URL %url is OK.', array(
        '%url' => $url,
      )));
      $this->submitForm($form, $form_state);
    }
    catch (Exception $e) {
      drupal_set_message(t('CARE WSDL URL %url failed.', array(
        '%url' => $url,
      )), 'error');
      drupal_set_message(t('Reverted to previous value.'), 'error');
    }
  }

}
