<?php

namespace Drupal\personal_digest\Controller;

use Drupal\user\Entity\User;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserDataInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller routines for user routes.
 */
class RemoteLogin extends ControllerBase {


  /**
   * The user data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * Constructs a UserController object.
   *
   * @param \Drupal\user\UserDataInterface $user_data
   *   The user data service.
   */
  public function __construct(UserDataInterface $user_data) {
    $this->userData = $user_data;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.data')
    );
  }

  /**
   * Returns the user password reset page.
   *
   * @param int $uid
   *   UID of user requesting reset.
   * @param int $timestamp
   *   The current timestamp.
   * @param string $hash
   *   Login link hash.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   The form structure or a redirect response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   If the login link is for a blocked user or invalid user ID.
   */
  public function page($uid, $timestamp, $hash) {
    $account = $this->currentUser();
    $redirect = FALSE;
    $incoming_user = User::load($uid);
    $config = $this->config('personal_digest.settings');
    // When processing the one-time login link, we have to make sure that a user
    // isn't already logged in.
    if ($account->isAuthenticated()) {
      if ($account->id() == $uid) {
        // The current user is already logged in.
      }
      elseif ($incoming_user->isActive()) {
        user_logout();
        user_login_finalize($incoming_user);
      }
      else {
        $this->messenger($this->t('User is blocked'));
        return $this->redirect('<front>');
      }
    }
    $generator = personal_digest_generator($incoming_user);
    $redirect = $generator->validate($timestamp, $hash, $config->get('remote_login_timeout'));

    if ($redirect) {
      $route_name = 'entity.entity_form_display.user.'.personal_digest_user_settings_form_mode();
      return $this->redirect($route_name, ['user' => $uid]);
    }
    elseif ($incoming_user) {
      $this->messenger($this->t('You have tried to use a one-time login link that in invalid or has expired. You will need to log in normally'), 'error');
      return $this->redirect('user.login');
    }
    // Blocked or invalid user ID, so deny access. The parameters will be in the
    // watchdog's URL for the administrator to check.
    throw new AccessDeniedHttpException();
  }

  /**
   * Test the digest for the current user and the last month.
   */
  public function test() {
    $recipient = $this->currentUser()->id();
    $user_data = \Drupal::service('user.data');
    $settings = $user_data->get('personal_digest', $recipient->id(), 'digest');
    if ($settings) {
      // Taken from the QueueWorker.
      \Drupal::service('plugin.manager.mail')
        ->mail('personal_digest',
          'digest',
          $recipient->getEmail(),
          $recipient->getPreferredLangcode(),
          [
            'user' => $recipient,
            'displays'  => $settings['displays'],
            'since' => strtotime('-1 month'),
          ]
        );
      $message = 'sent';
    }
    else {
      drupal_set_message("The current user has no personal digest settings. See user/" . $recipient->id() . "/digest");
      $message = 'not sent';
    }
    return ['#markup' => $message];
  }


}
