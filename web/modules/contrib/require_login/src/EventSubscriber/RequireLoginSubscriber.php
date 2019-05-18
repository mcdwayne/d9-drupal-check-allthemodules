<?php

namespace Drupal\require_login\EventSubscriber;

use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Login requirement.
 */
class RequireLoginSubscriber implements EventSubscriberInterface {

  /**
   * The request exception boolean.
   */
  protected $requestException;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The account proxy.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $accountProxy;

  /**
   * The path matcher under test.
   *
   * @var \Drupal\Core\Path\PathMatcher
   */
  protected $pathMatcher;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Session\AccountProxyInterface $account_proxy
   *   The account proxy.
   * @param \Drupal\Core\Path\PathMatcher $path_matcher
   *   The path matcher.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ConfigFactoryInterface $config_factory, RequestStack $request_stack, AccountProxyInterface $account_proxy, PathMatcher $path_matcher, MessengerInterface $messenger) {
    $this->moduleHandler = $module_handler;
    $this->configFactory = $config_factory;
    $this->requestStack = $request_stack;
    $this->accountProxy = $account_proxy;
    $this->pathMatcher = $path_matcher;
    $this->messenger = $messenger;
  }

  /**
   * Check login requirement for current request.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event response.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The configuration object.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The HTTP request.
   *
   * @return boolean
   *   Return FALSE if login not required. TRUE otherwise.
   */
  private function checkLogin($event, $config, $request) {
    $route_name = $request->get('_route');

    // Check 403/404 page exclusions.
    if ($event instanceof \Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent) {
      $exception = FlattenException::create($event->getException());

      switch ($exception->getStatusCode()) {
        case '403':
          if ($config->get('excluded_403')) {
            return FALSE;
          }
          break;

        case '404':
          if ($config->get('excluded_404')) {
            return FALSE;
          }
          break;
      }
    }

    // Check path exclusions.
    $excluded_paths = explode(PHP_EOL, $config->get('excluded_paths'));
    $excluded_paths[] = $config->get('auth_path');

    if ($this->pathMatcher->matchPath($request->getRequestUri(), implode(PHP_EOL, $excluded_paths))) {
      return FALSE;
    }

    // Standard login requirement checks.
    $checks = [
      // Check user session.
      ($this->accountProxy->getAccount()->id() > 0),
      // Check system.cron (/cron.php).
      ($route_name == 'system.cron'),
      // Check system.db_update (/update.php).
      ($route_name == 'system.db_update'),
      // Check user.* (/user/*).
      ($route_name == 'user.login' || $route_name == 'user.register' || $route_name == 'user.pass' || substr($route_name, 0, 10) === 'user.reset'),
      // Check Drush.
      (function_exists('drupal_is_cli') && drupal_is_cli()),
      // Check samlauth routes.
      (preg_match('/^samlauth./i', $route_name) && $route_name != 'samlauth.samlauth_configure_form'),
      // Check simplesamlphp_auth routes.
      ($route_name == 'simplesamlphp_auth.saml_login'),
    ];
    // Allow modules to alter $checks variable.
    $this->moduleHandler->alter('require_login_authcheck', $checks);

    foreach ($checks as $check) {
      if ($check) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Prepare redirect response.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event response.
   *
   * @return string|null
   *   Redirect URL including query string.
   */
  private function prepareLoginRedirect($event) {
    $config = $this->configFactory->get('require_login.config');
    $request = $this->requestStack->getCurrentRequest();

    if ($this->checkLogin($event, $config, $request)) {

      // Access denied warning message.
      if ($message = $config->get('deny_message')) {
        $messenger = $this->messenger;
        $messenger->addMessage($message, $messenger::TYPE_WARNING);
      }

      // Login form redirect path.
      if ($auth_path = $config->get('auth_path')) {
        $redirectPath = "internal:{$auth_path}";
      }
      else {
        $redirectPath = 'internal:/user/login';
      }

      // Login destination redirect path.
      if (!($destination = $config->get('destination_path'))) {
        $destination = $request->getRequestUri();
      }

      return Url::fromUri($redirectPath, ['query' => ['destination' => $destination]])->toString();
    }
    return NULL;
  }

  /**
   * Login redirect on KernelEvents::EXCEPTION.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event response.
   */
  public function exceptionRedirect(GetResponseEvent $event) {

    // Boolean to indicate request exception. Prevents additional login
    // requirement checks on KernelEvents::REQUEST which could cause
    // infinite loop redirects on protected pages.
    $this->requestException = TRUE;

    if ($redirect = $this->prepareLoginRedirect($event)) {
      $response = new RedirectResponse($redirect);
      $event->setResponse($response);
    }
  }

  /**
   * Login redirect on KernelEvents::REQUEST.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   The event response.
   */
  public function requestRedirect(GetResponseEvent $event) {
    if (!$this->requestException && ($redirect = $this->prepareLoginRedirect($event))) {
      $response = new RedirectResponse($redirect);
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::EXCEPTION][] = ['exceptionRedirect'];
    $events[KernelEvents::REQUEST][] = ['requestRedirect'];
    return $events;
  }

}
