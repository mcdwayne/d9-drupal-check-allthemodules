<?php

namespace Drupal\converter_tools\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Form for character counter.
 */
class ConverterToolsCheckSiteAvailableForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'converter_tools_check_site_available';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['converter_tools_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site/Server'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Check'),
    ];

    $form['converter_tools_result'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Status'),
      '#attributes' => ['readonly' => 'readonly'],
      '#disabled' => TRUE,
      '#size' => 8,
    ];

    if ($form_state->isRebuilding() && !empty($form_state->getValue('converter_tools_result'))) {

      $result = $form_state->getValue('converter_tools_result');

      $form['converter_tools_result']['#value'] = $result;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $url = $form_state->getValue('converter_tools_text');

    if (strpos($url, 'http://') === FALSE && strpos($url, 'https://') === FALSE) {
      $url = 'http://' . $url;
    }

    $valid_url = UrlHelper::isValid($url, TRUE);

    if (!$valid_url) {
      $form_state->setErrorByName('converter_tools_text', $this->t('Please enter a valid URL.'));
      $form['converter_tools_result']['#value'] = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $url = $form_state->getValue('converter_tools_text');

    $status = '';

    $timeout = 10;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

    $http_respond = curl_exec($ch);
    $http_respond = trim(strip_tags($http_respond));

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (($http_code == "200") || ($http_code == "302")) {
      $status = 'Online';
    }
    else {
      $status = 'offline';
    }

    curl_close($ch);

    $form_state->setValue('converter_tools_result', $status);

    $form_state->setRebuild();
  }

}
