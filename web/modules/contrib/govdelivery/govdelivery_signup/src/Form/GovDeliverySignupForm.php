<?php

/**
 * @file
 * Contains \Drupal\govdelivery_signup\Form\GovDeliverySignupForm.
 */

namespace Drupal\govdelivery_signup\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;

class GovDeliverySignupForm extends FormBase {
  /**
   * Hold the form configuration
   */
  private $config;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'govdelivery_signup';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $form_state->getBuildInfo()['args'][0];
    $values = $form_state->getValues();

    $email = $values['email'];
    $govdelivery_signup_server = $config['server'];
    $govdelivery_signup_clientcode = $config['client_code'];
    $url = $config['server'] . '/accounts/' . $config['client_code'] . '/subscribers/qualify?email=' . urlencode($values['email']);
    $response = new TrustedRedirectResponse($url);
    $form_state->setResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /*
    $values = $form_state->getValues();
    */
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form = [], FormStateInterface $form_state) {
    $this->config = $config = func_get_args()[2];
    $config['url'] = $config['server'] . '/accounts/' . $config['client_code'] . '/subscribers/qualify?';

    if ($config['js_enabled']) {
      $form['#attached']['library'][] = 'govdelivery_signup/signupForm';
      $form['#attached']['drupalSettings']['govDeliverySignup'] = $config;
    }

    $form['govdelivery_signup'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t($config['fieldset_desc']),
      '#collapsible' => FALSE,
    );
    if (!empty($config['description'])) {
      $form['govdelivery_signup']['#description'] = $this->t($config['description']);
    }
    $form['govdelivery_signup']['email'] = [
      '#type' => 'email',
      '#title' => $this->t($config['email_label']),
      '#attributes' => [
        'placeholder' => $config['email_placeholder'],
      ],
      '#maxlength' => 250,
      '#size' => 25,
      '#required' => TRUE,
    ];
    if (!empty($config['email_desc'])) {
      $form['govdelivery_signup']['email']['#description'] = t($config['email_desc']);
    }
    $form['govdelivery_signup']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t($config['button_label']),
    ];
    $form['#theme'] = 'govdelivery_signup_displayform';

    return $form;
  }
}
