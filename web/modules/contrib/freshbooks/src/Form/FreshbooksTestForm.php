<?php
/**
 * (c) MagnaX Software
 */

namespace Drupal\freshbooks\Form;


use Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Freshbooks\FreshBooksApi;

class FreshbooksTestForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'freshbooks_admin_test';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('freshbooks.settings');
    $apiDomain = $config->get('domain');
    $apiToken = $config->get('token');

    if (empty($apiDomain) || empty($apiToken)) {
      $form['container']['#type'] = 'container';
      $form['container']['notice'] = array(
        '#markup' => $this->t('Please specify both the API Service URL and API Authentication Token.'),
      );
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Run Test'),
      '#button_type' => 'primary',
      '#disabled' => (empty($apiDomain) || empty($apiToken)),
    );

    // By default, render the form using theme_system_config_form().
    $form['#theme'] = 'system_config_form';

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('freshbooks.settings');
    $apiDomain = $config->get('domain');
    $apiToken = $config->get('token');

    if (empty($apiDomain) || empty($apiToken)) {
      drupal_set_message($this->t('Please specify both the API Service URL and API Authentication Token.'), 'error');
      return;
    }

    /** @var FreshBooksApi $api */
    $api = Drupal::service('freshbooks.api');

    $api->setMethod('client.list');
    $api->request();
    if ($api->success()) {
      drupal_set_message($this->t('Connected to FreshBooks successfully. The API settings you have provided appear correct!'));
    }
    else {
      drupal_set_message($this->t('Could not connect to FreshBooks. Please check the API settings you have provided.'), 'error');
    }
  }
}
