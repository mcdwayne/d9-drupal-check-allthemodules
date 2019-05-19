<?php

namespace Drupal\widencollective\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Xss;
use GuzzleHttp\Exception\ConnectException;

/**
 * Implements the Widen Admin form.
 */
class AdminForm extends ConfigFormBase {

  protected $collectiveDomain;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'widencollective.admin_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'widencollective.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $widencollective_config = $this->config('widencollective.settings');

    $form['collective_domain'] = [
      '#type' => 'textfield',
      '#title' => t('Widen Collective Domain'),
      '#default_value' => $widencollective_config->get('collective_domain'),
      '#description' => t('example: demo.widencollective.com'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $collective_domain = Xss::filter($form_state->getValue('collective_domain'));
    $collective_domain = trim($collective_domain);
    if (!empty($collective_domain)) {
      // Make sure that we don't have http:// or https://.
      $this->collectiveDomain = preg_replace('#^https?://#', '', $collective_domain);
      $this->widencollectiveValidateCollectivePing($form_state);
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('widencollective.settings')
      ->set('collective_domain', $this->collectiveDomain)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Checks collective domain with an 80 and 443 ping.
   */
  private function widencollectiveValidateCollectivePing(FormStateInterface $form_state) {
    // Generate the ping endpoint non-SSL URL of the configured collective
    // domain.
    $endpoints = [
      'http' => 'http://' . $this->collectiveDomain . '/collective.ping',
      'https' => 'https://' . $this->collectiveDomain . '/collective.ping',
    ];

    foreach ($endpoints as $protocol => $endpoint) {
      try {
        // Process the response of the HTTP request.
        $response = \Drupal::httpClient()->get($endpoint);
        $status = $response->getStatusCode();

        // If ping returns a successful HTTP response, display a confirmation
        // message.
        if ($status == '200') {
          drupal_set_message(t('Validating Widen Collective domain (@protocol): OK!', [
            '@protocol' => $protocol,
          ]));
        }
        else {
          // If failed, display an error message.
          $form_state->setErrorByName('collective_domain', t('Validating Widen Collective domain (@protocol): @status', [
            '@protocol' => $protocol,
            '@status' => $status,
          ]));
        }
      }
      catch (ConnectException $e) {
        $form_state->setErrorByName('collective_domain', t('Unable to resolve widen collective domain.'));
      }
    }
  }

}
