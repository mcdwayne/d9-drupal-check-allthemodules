<?php

namespace Drupal\pki_ra\Processors;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;

/**
 * {@inheritdoc}
 */
class PKIRARegistrationProcessor extends PKIRAProcessor {

  const NODE_TYPE = 'pki_ra_registration';

  protected $registration;

  /**
   * {@inheritdoc}
   */
  public function __construct(Node $node) {
    try {
      if ($node->getType() != self::NODE_TYPE) {
        throw new Exception('Wrong registration type.');
      }
    }
    catch (Exception $e) {
      watchdog_exception('pki_ra', $e, '@class can only be instantiated with the @valid_type type, %invalid_type used.', [
        '@class' => __CLASS__,
        '@valid_type' => self::NODE_TYPE,
        '%invalid_type' => $node->getType(),
      ]);
    }
    $this->registration = $node;
  }

  /**
   * Get registration timeout.
   */
  public static function getRegistrationTimeoutInSeconds() {
    $window = \Drupal::config('pki_ra.settings')->get('registration_confirmation_window') ?: 2;
    return $window * 24 * 60 * 60;
  }

  /**
   * Validates the registration e-mail address provided by the user.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @see Drupal\Core\Render\Element\Url::validateUrl()
   */
  public static function validateEmailAddress(&$element, FormStateInterface $form_state, &$complete_form) {
    $email_address = trim($element['#value']);
    $form_state->setValueForElement($element, $email_address);

    if (!\Drupal::service('email.validator')->isValid($email_address, TRUE, TRUE)) {
      $form_state->setError($element, t('The entered e-mail address %email is not valid.', [
        '%email' => $email_address,
      ]));
    }

    $registration = self::getRegistrationByTitle($email_address);
    if ($registration != NULL) {
      if (self::isConfirmed($registration) == FALSE) {
        $confirmation_url = Url::fromRoute('pki_ra.registration.resend.verification')->toString();
        $form_state->setError($element, t('The e-mail address %email has not been verified, Click <a href=":send-verification">here</a> to send a verification email.', [
          '%email' => $email_address,
          ':send-verification' => $confirmation_url,
        ]));
      }

      if (self::emailAddressIsTaken($email_address) && self::isConfirmed($registration) == TRUE) {
        $login_url = Url::fromRoute('user.login')->toString();
        $form_state->setError($element, t('The e-mail address %email has already registered, Please <a href=":login-url">click here to login</a>.', [
          '%email' => $email_address,
          ':login-url' => $login_url,
        ]));
      }
    }
  }

  /**
   * Check if email address is already taken.
   */
  public static function emailAddressIsTaken($email_address) {
    if (is_object(user_load_by_mail($email_address))) {
      return TRUE;
    }
    if (!empty(\Drupal::entityQuery('node')
      ->condition('type', self::NODE_TYPE)
      ->condition('title', $email_address)
      ->execute())) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Set security token.
   */
  public function setSecurityToken($token = FALSE) {
    $this->registration->set('field_registration_code', $token ?: $this->getSecurityToken());
  }

  /**
   * Get security token.
   */
  public function getSecurityToken() {
    return Crypt::randomBytesBase64(55);
  }

  /**
   * Unset security token.
   */
  public function unsetSecurityToken() {
    $this->registration->set('field_registration_code', NULL);
    return $this;
  }

  /**
   * Send email verification.
   */
  public function sendEmailVerification() {
    $config = \Drupal::config('pki_ra.settings');

    $address = $this->registration->getTitle();
    $language = $this->getRegistrantLanguage()->getId();
    $parameters = ['url' => $this->getVerificationUrl()];
    // Allow modules to alter Email message.
    \Drupal::moduleHandler()->alter('pki_ra_email_verification_message', $parameters);
    $message = \Drupal::service('plugin.manager.mail')->mail('pki_ra', 'verification', $address, $language, $parameters);

    $success = Xss::filterAdmin($config->get('messages.verification_mail_success')['value']);
    $failure = Xss::filterAdmin($config->get('messages.verification_mail_failure')['value']);
    drupal_set_message(t(($message['result'] === TRUE) ? $success : $failure));
  }

  /**
   * Get registrant language.
   */
  protected function getRegistrantLanguage() {
    return \Drupal::languageManager()->getCurrentLanguage();
  }

  /**
   * Generates the clickable link a user is e-mailed to verify his/her e-mail address.
   *
   * @see user_pass_reset_url()
   */
  protected function getVerificationUrl() {
    $timestamp = REQUEST_TIME;

    return \Drupal::url('registration.verify', [
      'registration_id' => $this->registration->id(),
      'timestamp' => $timestamp,
      'hash' => $this->getRegistrationHash($timestamp),
    ],
    [
      'absolute' => TRUE,
      'language' => $this->getRegistrantLanguage(),
    ]
    );
  }

  /**
   * Generates a hash of the registration data for use in time-dependent URLs.
   *
   * @see user_pass_rehash()
   */
  public function getRegistrationHash($timestamp) {
    $security_key = $this->registration->get('field_registration_code')->getValue()[0]['value'];

    $data = $timestamp;
    $data .= $security_key;
    $data .= $this->registration->id();
    $data .= $this->registration->getTitle();
    return Crypt::hmacBase64($data, Settings::getHashSalt() . $security_key);
  }

  /**
   * Check if registration is confirmed.
   */
  public static function isConfirmed(Node $registration) {
    return $registration->get('field_registration_confirmed')->getValue()[0]['value'];
  }

  /**
   * Confirm registration.
   */
  public function confirmRegistration() {
    $this->registration->set('field_registration_confirmed', 1);
    $this->saveRegistration();
  }

  /**
   * Save registration.
   */
  public function saveRegistration() {
    $this->registration->save();
  }

  /**
   * Get registration with title.
   *
   * @param $email
   * @return \Drupal\Core\Entity\EntityInterface[]
   */
  public static function getRegistrationByTitle($email) {
    $registration = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
      'title' => $email,
    ]);
    // Get first registration.
    return (is_array($registration) && !empty($registration)) ? reset($registration) : NULL;
  }

}
