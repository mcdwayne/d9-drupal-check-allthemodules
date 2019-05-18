<?php

namespace Drupal\basicshib\Controller;

use Drupal\basicshib\AuthenticationHandlerInterface;
use Drupal\basicshib\Exception\AuthenticationException;
use Drupal\basicshib\Exception\RedirectException;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Utility\Error;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class LoginController.
 */
class LoginController extends ControllerBase {
  /**
   * @var PathValidatorInterface
   */
  protected $path_validator;

  /**
   * @var ImmutableConfig
   */
  protected $configuration;

  /**
   * @var AuthenticationHandlerInterface
   */
  protected $authentication_handler;

  /**
   * @var null|Request
   */
  protected $request;

  /**
   * Constructs a new LoginController object.
   *
   * @param AuthenticationHandlerInterface $authentication_handler
   * @param RequestStack $request_stack
   * @param ConfigFactoryInterface $config_factory
   * @param PathValidatorInterface $path_validator
   *
   * @throws InvalidPluginDefinitionException
   */
  public function __construct(AuthenticationHandlerInterface $authentication_handler, RequestStack $request_stack, ConfigFactoryInterface $config_factory, PathValidatorInterface $path_validator) {
    $this->request = $request_stack
      ->getCurrentRequest();

    $this->configuration = $config_factory
      ->get('basicshib.settings');

    $this->path_validator = $path_validator;

    $this->authentication_handler = $authentication_handler;
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('basicshib.authentication_handler'),
      $container->get('request_stack'),
      $container->get('config.factory'),
      $container->get('path.validator')
    );
  }

  /**
   * Login.
   *
   * @return array|RedirectResponse
   *   Either a render array or a redirect response.
   */
  public function login() {
    $exception = null;

    $messages = $this->configuration->get('messages');

    try {
      // Set the redirect response first so authentication doesn't get processed
      // when the redirect is invalid.
      $redirect_response = $this->getRedirectResponse();

      $this->authentication_handler->authenticate();
      return $redirect_response;
    }
    catch (RedirectException $exception) {
      switch ($exception->getCode()) {
        case RedirectException::BLOCKED_EXTERNAL:
          return ['#markup' => $messages['external_redirect_error']];

        case RedirectException::INVALID_PATH:
          return ['#markup' => $this->t('Invalid redirect path.')];

        default:
          return ['#markup' => $this->t('An unknown redirect error occurred')];
      }

    }
    catch (AuthenticationException $exception) {
      switch ($exception->getCode()) {
        case AuthenticationException::LOGIN_DISALLOWED_FOR_USER:
          return ['#markup' => $messages['login_disallowed_error']];
        case AuthenticationException::USER_BLOCKED:
          return ['#markup' => $messages['account_blocked_error']];
        case AuthenticationException::USER_CREATION_NOT_ALLOWED:
          return ['#markup' => $messages['user_creation_not_allowed_error']];
        default:
          return ['#markup' => $messages['generic_login_error']];
      }
    }
    finally {
      if ($exception !== null) {
        $this->getLogger('basicshib')->error(
          'Authentication failed: @message -- @backtrace_string',
          Error::decodeException($exception)
        );
      }
    }
  }

  /**
   * Get the redirect response.
   *
   * @return RedirectResponse
   * @throws RedirectException
   */
  private function getRedirectResponse() {
    $redirect_path = $this->request
      ->query
      ->get('after_login');

    if ($redirect_path === null) {
      $redirect_path = $this->configuration
        ->get('default_post_login_redirect_path');
    }

    $url = $this->path_validator
      ->getUrlIfValidWithoutAccessCheck($redirect_path);

    if ($url === false) {
      throw new RedirectException($this->t(
        'Redirect path @path is not valid',
        ['@path' => $redirect_path]
      ), RedirectException::INVALID_PATH);
    }

    if ($url->isExternal()) {
      throw new RedirectException($this->t(
        'Blocked attempt to redirect to external url @url',
        ['@url' => $redirect_path]
      ), RedirectException::BLOCKED_EXTERNAL);
    }

    try {
      return new RedirectResponse($redirect_path);
    }
    catch (\InvalidArgumentException $exception) {
      throw new RedirectException($this->t(
        'Error creating redirect: @message',
        ['@message' => $exception->getMessage()]
      ), RedirectException::INVALID_PATH, $exception);
    }
  }
}
