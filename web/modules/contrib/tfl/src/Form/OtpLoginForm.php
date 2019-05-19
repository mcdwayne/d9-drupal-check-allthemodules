<?php

namespace Drupal\tfl\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tfl\Controller\AccountChecker;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a OTP login form.
 */
class OtpLoginForm extends FormBase {

  
  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;
  
  /**
   * Account checker.
   *
   * @var \Drupal\tfl\Controller\AccountChecker
   */
  protected $accountChecker;

  /**
   * Constructs a new OtpLoginForm.
   *
   *
   */
  public function __construct(UserStorageInterface $user_storage) {
    $this->userStorage = $user_storage;
    $this->accountChecker = new AccountChecker();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'otp_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $requestUri = explode('/', $this->getRequest()->getRequestUri());

    if (is_array($requestUri) && (int) $requestUri[4] > 0 && is_string($requestUri[5])) {
      $uid = $requestUri[4];
      $otp_session_id = trim( $requestUri[5] );
      // Verify otp session id during OTP send from table.
      $isOtpSessionIdVerified = $this->accountChecker->isOtpSessionIdVerified( $uid, $otp_session_id );
      if (!$isOtpSessionIdVerified) {
        throw new AccessDeniedHttpException();
      }
      if ($isOtpSessionIdVerified && !empty($this->accountChecker->getShadowMobileNumber($uid))) {
          $mobile_number = $this->accountChecker->getShadowMobileNumber($uid);
          drupal_set_message('An OTP login verification is sent to your mobile number '.$mobile_number);
      }
      $form['otp'] = [
        '#type' => 'textfield',
        '#title' => $this->t('OTP'),
        '#size' => 60,
        '#description' => $this->t('Enter the OTP.'),
        '#required' => TRUE,
      ];

      $form['uid'] = [
        '#type' => 'hidden',
        '#value' => $uid,
      ];

      $form['#validate'][] = '::validateOtpAuthentication';
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Log in'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $this->userStorage->loadByProperties([
      'uid' => $form_state->getValue('uid'),
      'status' => 1,
    ]);
    $account = reset($user);
    // A destination was set, probably on an exception controller,.
    if (!$this->getRequest()->request->has('destination')) {
      $form_state->setRedirect(
        'entity.user.canonical',
        ['user' => $account->id()]
      );
    }
    else {
      $this->getRequest()->query->set('destination', $this->getRequest()->request->get('destination'));
    }
    if ($account->id()) {
      $this->accountChecker->clearUserOtpData($account->id());
    }
    user_login_finalize($account);

  }

  /**
   * Checks OTP validation.
   *
   * If successful, $form_state->get('uid') is set to the matching user ID.
   */
  public function validateOtpAuthentication(array &$form, FormStateInterface $form_state) {
    $uid = $form_state->getValue('uid');
    $isOtpVerified = $this->accountChecker->isOtpVerified($uid, $form_state->getValue('otp'));
    // Error validation.
    if (!isset($isOtpVerified)) {
      $form_state->setError($form, 'OTP service problem. Please try again.');
    }
    else {
      if (isset($isOtpVerified->Status) && ($isOtpVerified->Status === 'Error')) {
        if (isset($isOtpVerified->Details) && $isOtpVerified->Details === 'OTP Mismatch') {
          $form_state->setError($form, $isOtpVerified->Details);
        }
        else {
          $form_state->setError($form, 'OTP is not verified.');
        }
      }      
    }

  }

}
