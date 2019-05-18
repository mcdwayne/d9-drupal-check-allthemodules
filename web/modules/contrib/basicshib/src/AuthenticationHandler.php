<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/16/17
 * Time: 9:39 AM
 */

namespace Drupal\basicshib;


use Drupal\basicshib\Exception\AttributeException;
use Drupal\basicshib\Exception\AuthenticationException;
use Drupal\basicshib\Plugin\AuthFilterPluginInterface;
use Drupal\basicshib\Plugin\BasicShibPluginManager;
use Drupal\basicshib\Plugin\UserProviderPluginInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Url;

class AuthenticationHandler implements AuthenticationHandlerInterface {

  /**
   * @var AttributeMapperInterface
   */
  private $attribute_mapper;

  /**
   * @var SessionTracker
   */
  private $session_tracker;

  /**
   * @var UserProviderPluginInterface
   */
  private $user_provider;

  /**
   * @var AuthFilterPluginInterface[]
   */
  private $auth_filters = [];

  /**
   * @var array
   */
  private $handlers = [];

  /**
   * @var PathValidatorInterface
   */
  private $path_validator;

  /**
   * AuthenticationHandler constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   * @param RequestStack $request_stack
   * @param AttributeMapperInterface $attribute_mapper
   * @param BasicShibPluginManager $user_provider_plugin_manager
   * @param BasicShibPluginManager $auth_filter_plugin_manager
   *
   * @throws PluginException
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              RequestStack $request_stack,
                              AttributeMapperInterface $attribute_mapper,
                              BasicShibPluginManager $user_provider_plugin_manager,
                              BasicShibPluginManager $auth_filter_plugin_manager,
                              PathValidatorInterface $path_validator) {

    $this->session_tracker = new SessionTracker(
      $request_stack->getCurrentRequest()->getSession()
    );

    $plugins = $config_factory
      ->get('basicshib.settings')
      ->get('plugins');

    $this->attribute_mapper = $attribute_mapper;
    $this->user_provider = $user_provider_plugin_manager
      ->createInstance($plugins['user_provider']);

    foreach ($plugins['auth_filter'] as $name) {
      $this->auth_filters[$name] = $auth_filter_plugin_manager
        ->createInstance($name);
    }

    $this->handlers = $config_factory
      ->get('basicshib.settings')
      ->get('handlers');

    $this->path_validator = $path_validator;
  }

  /**
   * @inheritDoc
   */
  public function authenticate() {
    // Get key attributes
    $key_attributes = $this->getKeyAttributes();

    // Load the user account.
    $account = $this->user_provider
      ->loadUserByName($key_attributes['name']);

    if ($account) {
      $this->assertExistingUserLoginAllowed($account);
    }
    else {
      $this->assertUserCreationAllowed($key_attributes['name']);

      // create user
      $account = $this->user_provider
        ->createUser(
          $key_attributes['name'],
          $key_attributes['mail']
        );
    }

    $this->saveAccount($account, $key_attributes);

    $this->session_tracker->set($key_attributes['session_id']);

    $this->userLoginFinalize($account);
  }

  /**
   * @param UserInterface $account
   * @param array $key_attributes
   *
   * @throws AuthenticationException
   */
  private function saveAccount(UserInterface $account, array $key_attributes) {
    $message = $account->isNew()
      ? 'Saving new account for @name has failed'
      : 'Updating existing account for @name has failed';

    $code = $account->isNew()
      ? AuthenticationException::USER_CREATION_FAILED
      : AuthenticationException::USER_UPDATE_FAILED;

    if ($account->isNew()) {
      try {
        $account->save();
      }
      catch (EntityStorageException $exception) {
        throw AuthenticationException::createWithContext(
          $message,
          ['@name' => $account->getAccountName()],
          $code,
          $exception
        );
      }
    }
  }

  /**
   * Assert that an existing user is allowed to log in.
   *
   * @param UserInterface $account
   * @throws AuthenticationException
   */
  private function assertExistingUserLoginAllowed(UserInterface $account) {
    if ($account->isBlocked()) {
      $exception = AuthenticationException::createWithContext(
        'User @name is blocked and cannot be authenticated',
        ['@name' => $account->getAccountName()],
        AuthenticationException::USER_BLOCKED
      );
      throw $exception;
    }

    foreach ($this->auth_filters as $auth_filter) {
      if (!$auth_filter->isExistingUserLoginAllowed($account)) {
        $exception = AuthenticationException::createWithContext(
          '@message',
          [
            '@message' => $auth_filter->getError(
              AuthFilterPluginInterface::ERROR_EXISTING_NOT_ALLOWED, $account
            )
          ],
          AuthenticationException::LOGIN_DISALLOWED_FOR_USER
        );
        throw $exception;
      }
    }
  }

  /**
   * Assert that user creation is allowed.
   *
   * @throws AuthenticationException
   */
  private function assertUserCreationAllowed($name) {
    foreach ($this->auth_filters as $auth_filter) {
      if (!$auth_filter->isUserCreationAllowed()) {
        $exception = AuthenticationException::createWithContext(
          '@message, user=@name',
          [
            '@name' => $name,
            '@message' => $auth_filter->getError(
              AuthFilterPluginInterface::ERROR_CREATION_NOT_ALLOWED
            )
          ],
          AuthenticationException::USER_CREATION_NOT_ALLOWED
        );
        throw $exception;
      }
    }
  }

  /**
   * Get the key attributes 'session_id', 'name', and 'mail'
   *
   * @return array
   *   An associative array whose keys are:
   *   - session_id
   *   - name
   *   - mail
   *
   * @throws AuthenticationException
   */
  private function getKeyAttributes() {
    $attributes = [];

    foreach (['session_id', 'name', 'mail'] as $id) {
      try {
        $attributes[$id] = $this->attribute_mapper
          ->getAttribute($id, FALSE);
      }
      catch (AttributeException $exception) {
        throw AuthenticationException::createWithContext(
          'Missing required attribute @id',
          ['@id' => $id],
          AuthenticationException::MISSING_ATTRIBUTES,
          $exception
        );
      }
    }

    return $attributes;
  }

  /**
   * Checks user session and logs the user out if the session is not valid.
   *
   * @param Request $request
   * @param AccountProxyInterface $account
   * @return int
   */
  public function checkUserSession(Request $request, AccountProxyInterface $account) {
    // Handle anonymous user
    if ($account->isAnonymous()) {
      if ($this->session_tracker->exists()) {
        $this->session_tracker->clear();
        return self::AUTHCHECK_LOCAL_SESSION_EXPIRED;
      }
      return self::AUTHCHECK_IGNORE;
    }

    // Authenticated user who is not authenticated via shibboleth.
    if (!$this->session_tracker->exists()) {
      return self::AUTHCHECK_IGNORE;
    }

    // Authenticated user with expired shib session
    $session_id = $this->attribute_mapper->getAttribute('session_id', true);
    if (!$session_id) {
      $this->terminateSession($account);
      return self::AUTHCHECK_SHIB_SESSION_EXPIRED;
    }

    // Authenticated user whose tracked session id does not match the current
    // session id.
    if ($session_id !== $this->session_tracker->get()) {
      $this->terminateSession($account);
      return self::AUTHCHECK_SHIB_SESSION_ID_MISMATCH;
    }

    // Additional checks by auth filter plugins.
    foreach ($this->auth_filters as $auth_filter) {
      $value = $auth_filter->checkSession($request, $account);
      if ($value !== self::AUTHCHECK_IGNORE) {
        $this->terminateSession($account);
        return $value;
      }
    }

    return self::AUTHCHECK_IGNORE;
  }

  /**
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   */
  private function terminateSession(AccountProxyInterface $account) {
    $this->session_tracker->clear();
    user_logout();
  }

  /**
   * Finalize login.
   *
   * @param UserInterface $account
   */
  private function userLoginFinalize(UserInterface $account) {
    user_login_finalize($account);
  }

  /**
   * @inheritDoc
   */
  public function getLoginUrl() {
    $login_handler = $this->handlers['login'];
    $current_url = $this->path_validator->getUrlIfValidWithoutAccessCheck('<current>');
    if ($current_url) {
      $target = $current_url->toString();
    }
    else {
      $target = '/user';
    }

    $login_url = Url::fromUserInput($login_handler);

    if (!$login_url) {
      /// @todo Log something about this?
      return 'login_handler_not_valid';
    }

    $target_url = $this->path_validator->getUrlIfValid('/basicshib/login');
    $target_url->setAbsolute(TRUE);
    $target_url->setOption('query', ['after_login' => $target]);

    $login_url->setOption('query', ['target' => $target_url->toString()]);

    return $login_url->toString();
  }
}
