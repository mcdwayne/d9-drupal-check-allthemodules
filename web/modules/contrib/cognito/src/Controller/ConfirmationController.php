<?php

namespace Drupal\cognito\Controller;

use Drupal\cognito\Aws\CognitoInterface;
use Drupal\cognito\CognitoMessagesInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\externalauth\ExternalAuthInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller to confirm a new user account.
 */
class ConfirmationController extends ControllerBase {

  /**
   * The cognito service.
   *
   * @var \Drupal\cognito\Aws\Cognito
   */
  protected $cognito;

  /**
   * The messages service.
   *
   * @var \Drupal\cognito\CognitoMessagesInterface
   */
  protected $cognitoMessages;

  /**
   * The external auth service.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $externalAuth;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * ConfirmationController constructor.
   *
   * @param \Drupal\cognito\Aws\CognitoInterface $cognito
   *   The cognito service.
   * @param \Drupal\cognito\CognitoMessagesInterface $cognitoMessages
   *   The cognito messages service.
   * @param \Drupal\externalauth\ExternalAuthInterface $externalAuth
   *   The external auth service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(CognitoInterface $cognito, CognitoMessagesInterface $cognitoMessages, ExternalAuthInterface $externalAuth, EventDispatcherInterface $eventDispatcher) {
    $this->cognito = $cognito;
    $this->cognitoMessages = $cognitoMessages;
    $this->externalAuth = $externalAuth;
    $this->eventDispatcher = $eventDispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('cognito.aws'),
      $container->get('cognito.messages'),
      $container->get('externalauth.externalauth'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Confirms a user.
   *
   * @param string $base64_email
   *   The base64 encoded email.
   * @param string $confirmation_code
   *   The confirmation code.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect.
   */
  public function confirm($base64_email, $confirmation_code) {
    $email = base64_decode($base64_email);

    if (!$email || !$confirmation_code) {
      $this->messenger()->addMessage($this->t('Invalid email or confirmation code'), 'warning');
      return new RedirectResponse(Url::fromRoute('user.login')->toString(), 302);
    }

    $result = $this->cognito->confirmSignup($email, $confirmation_code);
    if ($result->hasError()) {
      $this->messenger()->addMessage($this->t('Your account could not be confirmed. @message', [
        '@message' => $result->getError(),
      ]), 'warning');
      return new RedirectResponse(Url::fromRoute('user.login')->toString(), 302);
    }

    // Complete the registration.
    $this->externalAuth->login($email, 'cognito');

    $this->messenger()->addMessage($this->cognitoMessages->registrationConfirmed());

    $url = Url::fromRoute('<front>');
    $event = new GenericEvent(NULL, ['url' => $url]);
    $this->eventDispatcher->dispatch('cognito.account_confirmed_redirect', $event);

    return new RedirectResponse($event->getArgument('url')->toString(), 302);
  }

}
