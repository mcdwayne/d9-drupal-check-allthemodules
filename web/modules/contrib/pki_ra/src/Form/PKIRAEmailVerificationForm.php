<?php

namespace Drupal\pki_ra\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;

/**
 * Form controller for e-mail verification forms.
 */
class PKIRAEmailVerificationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pki_ra_registration_verify_email_form';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\node\Entity\Node $registration
   *   The registration whose e-mail address is being verified.
   * @param string $expiration_date
   *   Formatted expiration date for the e-mail verificationlink.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   E-mail verification link hash.
   *
   * @see Drupal\user\Form\buildForm()
   */
  public function buildForm(array $form, FormStateInterface $form_state, Node $registration = NULL, $expiration_date = NULL, $timestamp = NULL, $hash = NULL) {
    $body = $this->config('pki_ra.settings')->get('messages.email_address_confirmation')['value'];

    $form['message'] = [
      '#markup' => $this->t(
        '<p>This is a one-time e-mail verification for %email_address and will expire on %expiration_date.</p>' .
        Xss::filterAdmin($body), [
          '%email_address' => $registration->getTitle(),
          '%expiration_date' => $expiration_date,
        ]
      )
    ];

    $form['#title'] = $this->t('E-mail Verification');
    $form['help'] = array('#markup' => '<p>' . $this->t('This e-mail verification can be used only once.') . '</p>');
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Validate my e-mail address'),
    );
    $form['#action'] = Url::fromRoute('registration.verify.confirm', [
      'registration_id' => $registration->id(),
      'timestamp' => $timestamp,
      'hash' => $hash,
    ])->toString();
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * This form works by submitting the hash and timestamp to the
   * registration.verify route with a 'confirm' action.
   *
   * @see Drupal\user\Form\submitForm()
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
