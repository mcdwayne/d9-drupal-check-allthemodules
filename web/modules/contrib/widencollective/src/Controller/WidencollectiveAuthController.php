<?php

namespace Drupal\widencollective\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\widencollective\WidencollectiveAuthService;
use Drupal\user\UserData;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Widencollective controller for the widencollective module.
 */
class WidencollectiveAuthController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The request stack factory service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The user data factory service.
   *
   * @var \Drupal\user\UserData
   */
  protected $userData;

  /**
   * Constructs a new WidencollectiveAuthController.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack factory.
   * @param \Drupal\user\UserData $user_data
   *   The user data factory.
   */
  public function __construct(RequestStack $request_stack, UserData $user_data) {
    $this->request = $request_stack;
    $this->userData = $user_data;
  }

  /**
   * Menu callback from Widen Collective to complete authorization process.
   */
  public function authenticate() {
    // Get the code returned by the Widen Collective API endpoint, if available.
    $code = $this->request->getCurrentRequest()->query->get('code');
    $user_id = $this->request->getCurrentRequest()->query->get('uid');

    $user = User::load($user_id);

    if (isset($code) && !empty($user)) {
      // Save returned code to the current user profile.
      $this->handleAuthentication($code, $user);
      drupal_set_message($this->t('Account authorized to Widen Collective.'));
    }
    // If user does not exists.
    elseif (empty($user)) {
      drupal_set_message($this->t('User does not exists.'), 'error');
    }
    // If not return an error message when authentication process returns to
    // site.
    else {
      drupal_set_message($this->t('Authorization Denied. Widen Collective did not provide an auth code.'), 'error');
    }
    return $this->redirect('user.page');
  }

  /**
   * Checks whether given account is valid and updates account information.
   *
   * @param string $auth_code
   *   The authorization code provided during user creation.
   *
   * @todo improve function documentation block.
   */
  private function handleAuthentication($auth_code, $user) {
    $response = WidencollectiveAuthService::authenticate($auth_code);

    // If account is valid is and a token code has been provide, update the
    // account of the current user and set widen credentials saving the
    // widen_username and widen_token values.
    if (isset($response->username) && isset($response->access_token)) {
      $account = [
        'widen_username' => $response->username,
        'widen_token' => $response->access_token,
      ];

      // Store widen account details.
      $this
        ->userData
        ->set('widencollective', $user->id(), 'account', $account);

      // Redirect back to user edit form.
      $redirect = Url::fromRoute('entity.user.edit_form', ['user' => $user->id()])->toString();
      $response = new RedirectResponse($redirect);
      $response->send();

      return;
    }
    // Else, display an user message to the user.
    else {
      $error_msg = $this->t('Authorization Failure');
      if (isset($response->error)) {
        $error_msg .= ' ' . $this->t('[@error: @description]', ['@error' => $response->error, '@description' => $response->description]);
      }

      drupal_set_message($error_msg, 'error');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('user.data')
    );
  }

}
