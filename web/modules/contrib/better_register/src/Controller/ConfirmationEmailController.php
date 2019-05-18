<?php

namespace Drupal\better_register\Controller;

use Drupal;
use Drupal\better_register\Form\UserRegisterForm;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\UserStorageInterface;

/**
 * Class ConfirmationEmailController.
 *
 * @package Drupal\better_register\Controller
 */
class ConfirmationEmailController extends ControllerBase {

  protected $userStorage;

  /**
   * Implements the constuct for create class object.
   */
  public function __construct(UserStorageInterface $user_storage) {
    $this->userStorage = $user_storage;
  }

  /**
   * Create dependency injection for the class.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('user')
    );
  }

  public function emailNotConfirmed(AccountInterface $account) {
    // @todo Translate to english (custom template?)
    return [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => 'Su dirección de email no ha sido confirmada, por favor, haga clic en el enlace que le enviamos por correo cuando completó su registro. Si lo desea haga <a href="' .
        Url::fromRoute('better_register.confirmation_email_controller_send_email', [
          'account' => $account->id(),
        ])->toString() .
        '">clic aquí para que le reenviemos otro correo de verificación</a>.',
    ];
  }

  public function sendEmail(AccountInterface $account) {
    $user = $this->userStorage->load($account->id());

    if ($user->hasRole(UserRegisterForm::EMAIL_CONFIRMED_ROLE)) {
      drupal_set_message($this->t('Email address already verified.'));
    }
    else {
      _user_mail_notify('register_no_approval_required', $account);
      drupal_set_message($this->t('Verification email sent.'));
    }

    return $this->redirect('<front>');
  }

  public function verifyEmail(AccountInterface $account, string $hash): RedirectResponse {
    if ($hash == static::getUserHash($account)) {
      $user = $this->userStorage->load($account->id());
      $user->addRole(UserRegisterForm::EMAIL_CONFIRMED_ROLE);
      $user->save();

      drupal_set_message($this->t('Your email address has been confirmed. Thanks.'));

      if (Drupal::currentUser()->isAnonymous()) {
        return $this->redirect('user.login');
      }
      else {
        return $this->redirect('<front>');
      }
    }
    else {
      throw new AccessDeniedHttpException();
    }
  }

  private static function getUserHash(AccountInterface $account) {
    return md5($account->getEmail() . $account->getPreferredLangcode());
  }

  public static function getActivationUrl(AccountInterface $account): Url {
    return Url::fromRoute('better_register.confirmation_email_controller_verify_email', [
      'account' => $account->id(),
      'hash' => static::getUserHash($account),
    ], ['absolute' => TRUE]);
  }

}
