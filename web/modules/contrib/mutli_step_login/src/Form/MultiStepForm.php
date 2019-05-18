<?php

namespace Drupal\multi_step\Form;

use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserStorageInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides class extending Base class implementing system configuration forms.
 */
class MultiStepForm extends ConfigFormBase {
  protected $step = 1;
  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;
  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;
  /**
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;
  /**
   * The user authentication object.
   *
   * @var \Drupal\user\UserAuthInterface
   */
  protected $userAuth;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'multi_step_form';
  }

  /**
   * Constructs a new UserPasswordBlock plugin.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\user\UserAuthInterface $user_auth
   *   The user authentication object.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   */
  public function __construct(FormBuilderInterface $formBuilder, UserStorageInterface $user_storage, UserAuthInterface $user_auth, FloodInterface $flood) {
    $this->userStorage = $user_storage;
    $this->formBuilder = $formBuilder;
    $this->userAuth = $user_auth;
    $this->flood = $flood;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('user.auth'),
      $container->get('flood')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $res = [];
    $value = [];
    switch ($this->step) {
      case 1:
        $form['combo'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Email and Username'),
          '#required' => TRUE,
        ];
        $button_label = $this->t('Next');
        $form['actions']['submit']['#value'] = $button_label;
        break;

      case 2:
        $value = $form_state->getValue($res);
        if ($value['res']['name']) {
          $form = $this->formBuilder->getForm('Drupal\user\Form\UserLoginForm');
          $value = $form_state->getValue([]);
          $form['name']['#value'] = $value['combo'];
          $button_label = $this->t('Log in');
          $form['actions']['submit']['#value'] = $button_label;
          return $form;
        }
        break;
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($this->step == 1) {
      $name = $this->validateusernameEmail($form, $form_state);
      $set_result = $form_state->setValue('res', ['name' => $name]);
      if ($this->step < 2) {
        $form_state->setRebuild();
        $this->step++;
      }
      return $set_result;
    }
    if ($this->step == 2) {
      $this->validateName($form, $form_state);
      $this->validateAuthentication($form, $form_state);
      $this->validateFinal($form, $form_state);
    }
  }

  /**
   * Sets an error if supplied username has been blocked.
   */
  public function validateName(array &$form, FormStateInterface $form_state) {
    if (!$form_state->isValueEmpty('name') && user_is_blocked($form_state->getValue('name'))) {
      // Blocked in user administration.
      $form_state->setErrorByName('name', $this->t('The username %name has not been activated or is blocked.', ['%name' => $form_state->getValue('name')]));
    }
  }

  /**
   * Function validating username and email is exist or not.
   */
  public function validateusernameEmail($form, FormStateInterface $form_state) {
    $storage = $form_state->getStorage($this->step);
    $form_state->setStorage($storage);
    $combovalue = $form_state->getValue([]);
    $name_value = trim($combovalue['combo']);
    // Try to load by email.
    $users = $this->userStorage->loadByProperties(['mail' => $name_value]);
    if (empty($users)) {
      // No success, try to load by name.
      $users = $this->userStorage->loadByProperties(['name' => $name_value]);
    }
    $account = reset($users);
    if ($account && $account->id()) {
      // Blocked accounts cannot request a new password.
      if (!$account->isActive()) {
        $form_state->setErrorByName('name', $this->t('%name is blocked or has not been activated yet.', ['%name' => $name_value]));
      }
      else {
        return TRUE;
      }
    }
    else {
      global $base_url;
      $response = new RedirectResponse($base_url . '/user/register');
      $response->send();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->step == 2) {
      $user = $this->userStorage->load($form_state->get('uid'));
      if (isset($user)) {
        $form_state->setRedirect(
        'entity.user.canonical',
        ['user' => $user->id()]
        );
        user_login_finalize($user);
      }
    }
  }

  /**
   * Checks supplied username/password against local users table.
   *
   * If successful, $form_state->get('uid') is set to the matching user ID.
   */
  public function validateAuthentication(array &$form, FormStateInterface $form_state) {
    $input = &$form_state->getUserInput();
    $name = $input['name'];
    $pass = $input['pass'];
    $form_state->setValue('pass', $pass);

    $password = trim($form_state->getValue('pass'));
    $flood_config = $this->config('user.flood');
    if (!$form_state->isValueEmpty('name') && strlen($password) > 0) {
      // Do not allow any login from the current user's IP if the limit has been
      // reached. Default is 50 failed attempts allowed in one hour. This is
      // independent of the per-user limit to catch attempts from one IP to log
      // in to many different user accounts.  We have a reasonably high limit
      // since there may be only one apparent IP
      // for all users at an institution.
      if (!$this->flood->isAllowed('user.failed_login_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
        $form_state->set('flood_control_triggered', 'ip');
        return;
      }
      $accounts = $this->userStorage->loadByProperties(['name' => $form_state->getValue('name'), 'status' => 1]);
      $account = reset($accounts);
      if ($account) {
        if ($flood_config->get('uid_only')) {
          // Register flood events based on the uid only, so they apply for any
          // IP address. This is the most secure option.
          $identifier = $account->id();
        }
        else {
          // The default identifier is a combination of uid and IP address. This
          // is less secure but more resistant to denial-of-service attacks that
          // could lock out all users with public user names.
          $identifier = $account->id() . '-' . $this->getRequest()->getClientIP();
        }
        $form_state->set('flood_control_user_identifier', $identifier);

        // Don't allow login if the limit for this user has been reached.
        // Default is to allow 5 failed attempts every 6 hours.
        if (!$this->flood->isAllowed('user.failed_login_user', $flood_config->get('user_limit'), $flood_config->get('user_window'), $identifier)) {
          $form_state->set('flood_control_triggered', 'user');
          return;
        }
      }
      // We are not limited by flood control, so try to authenticate.
      // Check for user email account.
      if (strpos($name, "@") == TRUE) {
        $email_validation = multi_step_check_for_existing_user_email($name);
        if (!$email_validation || !isset($email_validation['mail'])) {
          $form_state->setErrorByName('name', $this->t('The email address provided could not be found'));
          return FALSE;
        }
        else {
          $email = $email_validation['mail'];
          $uid = multi_step_get_uid_from_email($email, $password);
        }
      }
      // Check account with username.
      /****** close if ****/
      else {
        $username_validation = multi_step_check_for_existing_user_name($name);
        if (!$username_validation || !isset($username_validation['name'])) {
          $form_state->setErrorByName('name', $this->t('The username provided could not be found'));
          return FALSE;
        }
        else {
          $name = $username_validation['name'];
          $uid = $this->userAuth->authenticate($form_state->getValue('name'), $password);
        }
      }
      $form_state->set('uid', $uid);
    }
  }

  /**
   * Checks if user was not authenticated, or if too many logins were attempted.
   *
   * This validation function should always be the last one.
   */
  public function validateFinal(array &$form, FormStateInterface $form_state) {
    $flood_config = $this->config('user.flood');
    if (!$form_state->get('uid')) {
      // Always register an IP-based failed login event.
      $this->flood->register('user.failed_login_ip', $flood_config->get('ip_window'));
      // Register a per-user failed login event.
      if ($flood_control_user_identifier = $form_state->get('flood_control_user_identifier')) {
        $this->flood->register('user.failed_login_user', $flood_config->get('user_window'), $flood_control_user_identifier);
      }

      if ($flood_control_triggered = $form_state->get('flood_control_triggered')) {
        if ($flood_control_triggered == 'user') {
          $form_state->setErrorByName('name', $this->formatPlural($flood_config->get('user_limit'), 'There has been more than one failed login attempt for this account. It is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', 'There have been more than @count failed login attempts for this account. It is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', [':url' => $this->url('user.pass')]));
        }
        else {
          // We did not find a uid, so the limit is IP-based.
          $form_state->setErrorByName('name', $this->t('Too many failed login attempts from your IP address. This IP address is temporarily blocked. Try again later or <a href=":url">request a new password</a>.', [':url' => $this->url('user.pass')]));
        }
      }
      else {
        // Use $form_state->getUserInput() in the error message to guarantee
        // that we send exactly what the user typed in. The value from
        // $form_state->getValue() may have been modified by validation
        // handlers that ran earlier than this one.
        $user_input = $form_state->getUserInput();
        $query = isset($user_input['name']) ? ['name' => $user_input['name']] : [];
        $form_state->setErrorByName('name', $this->t('Unrecognized username or password. <a href=":password">Forgot your password?</a>',
        [':password' => $this->url('user.pass', [], ['query' => $query])]));
        $accounts = $this->userStorage->loadByProperties(['name' => $form_state->getValue('name')]);
        if (!empty($accounts)) {
          $this->logger('user')->notice('Login attempt failed for %user.', ['%user' => $form_state->getValue('name')]);
        }
        else {
          // If the username entered is not a valid user,
          // only store the IP address.
          $this->logger('user')->notice('Login attempt failed from %ip.', ['%ip' => $this->getRequest()->getClientIp()]);
        }
      }
    }
    elseif ($flood_control_user_identifier = $form_state->get('flood_control_user_identifier')) {
      // Clear past failures for this user so as not to block a user who might
      // log in and out more than once in an hour.
      $this->flood->clear('user.failed_login_user', $flood_control_user_identifier);
    }
  }

}
