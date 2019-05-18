<?php

namespace Drupal\sarbacane\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SubscribeForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sarbacane_subscribe';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = reset($form_state->getBuildInfo()['args']);
    $form['description'] = ['#markup' => $config['description']];
    $form['email'] = [
      '#type' => 'email',
      '#placeholder' => $config['placeholder'],
      '#required' => TRUE,
    ];
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $config['button_text'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   * @see https://developers.sarbacane.com/docs#ajouter-un-contact
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = reset($form_state->getBuildInfo()['args']);

    //TODO dependency injection
    $client = \Drupal::httpClient();

    $listId = $config['listId'];

    $data = [
      'listId' => $listId,
      'email' => $form_state->getValue('email'),
    ];
    $headers = [
      'accountId' => $config['accountId'],
      'apiKey' => $config['apiKey'],
    ];
    $url = "https://sarbacaneapis.com/v1/lists/$listId/contacts";
    try {
      $response = $client->post($url, [
        'headers' => $headers,
        'json' => $data,
      ]);
      if ($response->getStatusCode() == '200') {
        drupal_set_message($config['message']);
      }
      else {
        $this->showError($response->getReasonPhrase(), $url);
      }
    } catch (\Exception $e) {
      $this->showError($e->getMessage(), $url);
    }
  }

  protected function showError($message, $url) {
    drupal_set_message($this->t("HTTP request to @url failed with error: @error.", [
      '@url' => $url,
      '@error' => $message
    ]), 'error');
  }
}
