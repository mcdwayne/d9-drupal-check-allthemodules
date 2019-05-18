<?php

namespace Drupal\cachetag_notify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

/**
 * Defines a form to configure module settings.
 */
class CachetagNotifySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'cachetag_notify_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cachetag_notify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get all settings
    $config = $this->config('cachetag_notify.settings');

    $form['endpoint'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint URL'),
      '#description' => $this->t('URL of where the JSON encoded cachetag data will be POSTed.'),
      '#default_value' => $config->get('endpoint'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!$this->isEndpointValid($form_state->getValue('endpoint'))) {
      $form_state->setErrorByName('endpoint', $this->t('Invalid endpoint URL.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('cachetag_notify.settings');

    $config
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->save();

    parent::submitForm($form, $form_state);
  }

  protected function isEndpointValid($endpoint) {
    if (UrlHelper::isValid($endpoint, TRUE)) {
      try {
        $response = \Drupal::httpClient()->post($endpoint, []);
        if ($response->getStatusCode() === 200) {
          return TRUE;
        }
        return FALSE;
      }
      catch (ConnectException $e) {
        return FALSE;
      }
      catch (RequestException $e) {
        return FALSE;
      }
      catch (Exception $e) {
        return FALSE;
      }
    }
  }

}

