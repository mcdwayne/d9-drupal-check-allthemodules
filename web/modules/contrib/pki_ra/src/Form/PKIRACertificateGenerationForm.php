<?php

namespace Drupal\pki_ra\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Form controller for certificate generation forms.
 */
class PKIRACertificateGenerationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pki_ra_generate_certificate';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param integer $registration_id
   *   The ID of the registration associated with this certificate generation.
   *
   * @todo On submission, display a message saying to enable JS & hit the Back button.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $registration_id = NULL) {
    $config = $this->config('pki_ra.settings');

    $form['#title'] = $this->t('Generate Certificate Bundle');
    $form['message'] = ['#markup' => Xss::filterAdmin($config->get('messages.certificate_generation_header')['value'])];
    $form['help'] = ['#markup' => Xss::filterAdmin($config->get('messages.certificate_generation_help')['value'])];

    $form['registration_id'] = [
      '#type' => 'hidden',
      '#value' => $registration_id,
    ];

    $form['key_type_and_size'] = [
      '#type' => 'select',
      '#title' => t('Key type'),
      '#options' => [
        'ECC' => t('Elliptic Curve'),
        'RSA' => t('RSA'),
      ],
      '#default_value' => 'ECC',
      '#description' => t('A sensible default will be chosen. Feel free to skip unless you have a preference.'),
    ];

    $form['password'] = [
      '#type' => 'password_confirm',
      '#after_build' => array('pki_ra_alter_element_password_confirm'),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate certificate bundle'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * This form shouldn't actually get submitted to the server as JS running on
   * the client will submit everything we need via an AJAX call. However, we
   * need to convey that (1) JavaScript must be enabled, and that (2) users need
   * to hit the Back button after enabling it.  This messaging will be displayed
   * on the resulting page.
   *
   * @see Drupal\user\Form\submitForm()
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
