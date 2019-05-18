<?php

namespace Drupal\pki_ra\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\pki_ra\Form\PKIRACertificateGenerationForm;
use Drupal\pki_ra\Form\PKIRAEmailVerificationForm;
use Drupal\pki_ra\Processors\PKIRACertificateProcessor;
use Drupal\pki_ra\Processors\PKIRARegistrationProcessor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for PKI RA routes.
 */
class PKIRAController extends ControllerBase {

  public function access(RouteMatchInterface $route_match, AccountInterface $account, Request $request = NULL) {
    switch ($route_match->getRouteName()) {

      // Alter read access to records.
      case 'entity.node.canonical':
        $restricted_node_types = [
          PKIRARegistrationProcessor::NODE_TYPE,
          PKIRACertificateProcessor::NODE_TYPE,
        ];

        // Allow access to everything except what we're restricting.
        if (!in_array($route_match->getParameter('node')->getType(), $restricted_node_types)) {
          return AccessResult::allowed();
        }

        // Allow the current user to view this registration only if it's his/hers (or is an administrator).
        $registered_user = $route_match->getParameter('node')->getOwner();
        $registered_user_id = isset($registered_user) ? $registered_user->id() : 0;
        return AccessResult::allowedIf(
          $account->hasPermission('administer pki registration') || (
            $account->isAuthenticated() &&
            (isset($registered_user) && ($registered_user_id == $account->id()))
          ));

      default:
        return AccessResult::allowed();
    }
  }

  public function beginRegistrationProcess(Request $request) {
    $introductory_message = $this->config('pki_ra.settings')->get('messages.introduction')['value'];
    $start_url = Url::fromRoute('node.add', array('node_type' => PKIRARegistrationProcessor::NODE_TYPE));
    $start_link = Link::fromTextAndUrl(t('Begin PKI Registration Process'), $start_url)->toRenderable();

    return [
      '#markup' => Xss::filterAdmin($introductory_message) . render($start_link),
    ];
  }

  /**
   * Redirect to the registrant e-mail verification form.
   *
   * In order to never disclose a reset link via a referrer header this
   * controller must always return a redirect response.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param int $registration_id
   *   The ID of the registration being verified.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Registration link hash.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   *
   * @see Drupal\user\Controller\UserController::resetPass()
   */
  public function redirectToEmailVerificationForm(Request $request, $registration_id, $timestamp, $hash) {
    // When processing the one-time e-mail verification link, we have to make
    // sure that a user isn't already logged in.
    if ($this->currentUser()->isAuthenticated()) {
      user_logout();
      // We need to begin the redirect process again because logging out will
      // destroy the session.
      return $this->redirect(
        'registration.verify',
        [
          'registration_id' => $registration_id,
          'timestamp' => $timestamp,
          'hash' => $hash,
        ]
      );
    }

    $session = $request->getSession();
    $session->set('pki_ra_email_verification_hash', $hash);
    $session->set('pki_ra_email_verification_timestamp', $timestamp);
    return $this->redirect(
      'registration.verify.form',
      ['registration_id' => $registration_id]
    );
  }

  /**
   * Returns the registration e-mail verification form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param int $registration_id
   *   ID of the registration we're verifying.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The form structure or a redirect response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   * If the timeout or hash are not available in the session.
   *   * If there is no registration matching the ID.
   *   * If the registration has already been confirmed.
   *
   * @see Drupal\user\Controller\UserController::getResetPassForm()
   */
  public function getEmailVerificationForm(Request $request, $registration_id) {
    $session = $request->getSession();
    $timestamp = $session->get('pki_ra_email_verification_timestamp');
    $hash = $session->get('pki_ra_email_verification_hash');
    // As soon as the session variables are used they are removed to prevent the
    // hash and timestamp from being leaked unexpectedly. This could occur if
    // the user does not click on the log in button on the form.
    $session->remove('pki_ra_email_verification_timestamp');
    $session->remove('pki_ra_email_verification_hash');

    if (!$hash || !$timestamp ||
        !is_object($registration = Node::load($registration_id)) ||
        ($registration->getType() != PKIRARegistrationProcessor::NODE_TYPE) ||
        PKIRARegistrationProcessor::isConfirmed($registration)) {
      throw new AccessDeniedHttpException();
    }

    $timeout = PKIRARegistrationProcessor::getRegistrationTimeoutInSeconds();
    $expiration_date = \Drupal::service('date.formatter')->format($timestamp + $timeout, 'long');
    return $this->formBuilder()->getForm(PKIRAEmailVerificationForm::class, $registration, $expiration_date, $timestamp, $hash);
  }

  /**
   * Validates registration, hash, and timestamp; confirms e-mail validation if correct.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param int $registration_id
   *   Registration ID of the confirmation request.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Registration confirmation link hash.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Returns a redirect to the certificate generation form if the information
   *   is correct.  If it isn't, we're redirected to the beginning of the
   *   process notifying the user.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If there is no such registration, or it has already been confirmed.
   *
   * @see Drupal\user\Controller\UserController\resetPassLogin()
   */
  public function confirmRegistration(Request $request, $registration_id, $timestamp, $hash) {
    $current = REQUEST_TIME;
    $registration = Node::load($registration_id);

    if ($registration === NULL || PKIRARegistrationProcessor::isConfirmed($registration)) {
      throw new AccessDeniedHttpException();
    }

    $processor = new PKIRARegistrationProcessor($registration);

    if ((($current - $timestamp) <= $processor->getRegistrationTimeoutInSeconds()) &&
        ($timestamp >= $registration->getCreatedTime()) && ($timestamp <= $current) &&
        Crypt::hashEquals($hash, $processor->getRegistrationHash($timestamp))) {

      $processor->confirmRegistration();
      $this->logRegistrationConfirmationAndNotifyUser($registration, $timestamp);

      // Update data to eoi progress table.
      $table_data = [
        'registration_id' => $registration_id,
        'eoi_method' => 'email',
        'status' => 'Complete',
        'updated' => $current,
      ];
      $progress_manager = \Drupal::service('pki_ra.eoi_progress_manager');
      $progress_manager->userEoiSourceProgress($table_data);

      $token = $processor->getSecurityToken();
      $_SESSION['pki_ra_email_verification_success_' . $registration_id] = $token;
      return $this->redirectToCertificateGenerationForm($registration_id, $token);
    }

    drupal_set_message($this->t('You have tried to use a one-time e-mail verification link that has either been used, expired, or is no longer valid. Please restart the registration process using the form below.'), 'error');
    return $this->redirect('node.add', ['node_type' => PKIRARegistrationProcessor::NODE_TYPE]);
  }

  protected function logRegistrationConfirmationAndNotifyUser($registration, $timestamp) {
    $body = $this->config('pki_ra.settings')->get('messages.email_address_validated')['value'];

    $this->getLogger('pki_ra')->notice('Registrant %email used one-time e-mail verification link at time %timestamp.', [
      '%email' => $registration->getTitle(),
      '%timestamp' => $timestamp,
    ]);

    drupal_set_message(Xss::filterAdmin($body));
  }

  protected function redirectToCertificateGenerationForm($registration_id, $token) {
    return $this->redirect(
      'registration.display.certificate.generation.form',
      ['registration_id' => $registration_id],
      [
        'query' => ['registration-success-token' => $token],
        'absolute' => TRUE,
      ]
    );
  }

  /**
   * Returns the certificate generation form.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param int $registration_id
   *   ID of the registration.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The form structure or a redirect response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *    If the security token is not available in the session.
   *
   * @see Drupal\user\AccountForm::form()
   */
  public function displayCertificateGenerationForm(Request $request, $registration_id) {
    $session_registration_key = 'pki_ra_email_verification_success_' . $registration_id;

    if (($registration_success_token = $request->get('registration-success-token')) &&
        isset($_SESSION[$session_registration_key]) &&
        Crypt::hashEquals($_SESSION[$session_registration_key], $registration_success_token)) {

      unset($_SESSION[$session_registration_key]);

      $processor = new PKIRARegistrationProcessor(Node::load($registration_id));
      $csr_token = $processor->getSecurityToken();
      $processor->setSecurityToken($csr_token);
      $processor->saveRegistration();
      $_SESSION['pki_ra_csr_token_' . $registration_id] = $csr_token;

      return $this->formBuilder()->getForm(PKIRACertificateGenerationForm::class, $registration_id);
    }
    else {
      throw new AccessDeniedHttpException();
    }
  }

  /**
   * EOI Sources progress indicator page.
   *
   * @param AccountInterface $user
   * @return array
   */
  public function displayUserEoiSourcesProgress($user) {
    $block_manager = \Drupal::service('plugin.manager.block');
    $block_config = [];
    $block_plugin = $block_manager->createInstance('eoi_progress_indicator', $block_config);
    return [
      '#markup' => render($block_plugin->build()),
    ];
  }

}
