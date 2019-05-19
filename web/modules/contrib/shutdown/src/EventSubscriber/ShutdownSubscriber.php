<?php

namespace Drupal\shutdown\EventSubscriber;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxy;

/**
 * Event Subscriber ShutdownSubscriber.
 */
class ShutdownSubscriber implements EventSubscriberInterface {

  /**
   * HTTP redirect code.
   *
   * @var int
   */
  private $redirectCode = 307;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $accountProxy;

  /**
   * Shutdown settings config object.
   *
   * @var Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs the shutdown subscriber.
   *
   * @param \Drupal\Core\Session\AccountProxy $account_proxy
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler class to use for third-party modules alter calls.
   */
  public function __construct(AccountProxy $account_proxy, ConfigFactory $config_factory, ModuleHandlerInterface $module_handler) {
    $this->accountProxy = $account_proxy;
    $this->config = $config_factory->get('shutdown.settings');
    $this->moduleHandler = $module_handler;
  }

  /**
   * Reacts on KernelEvents::REQUEST event.
   */
  public function onRequest(GetResponseEvent $event) {
    if ($this->config->get('shutdown_enable') == 1 && !$this->accountProxy->hasPermission('navigate shut website') && !function_exists('drush_main')) {
      $excluded_route_names = $this->getExcludedRouteNames();
      $redirect_page = $this->config->get('shutdown_redirect_page');

      // Check if current URL belongs to excluded route names.
      $current_route_name = \Drupal::service('current_route_match')->getRouteName();
      if (in_array($current_route_name, $excluded_route_names)) {
        return;
      }

      // Check if the request should be redirected.
      $current_url = Url::fromRoute('<current>');
      if (!$this->isExcludedPath('/' . $current_url->getInternalPath()) && !empty($redirect_page)) {
        if (UrlHelper::isExternal($redirect_page)) {
          $url = $redirect_page;
        }
        else {
          $url = Url::fromUserInput($redirect_page)->toString();
        }

        // Redirect user to the specified location.
        $response = new RedirectResponse($url, $this->redirectCode);
        $response->send();
        exit();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::REQUEST][] = ['onRequest'];
    return $events;
  }

  /**
   * Get a list of paths to exclude from shutdown.
   *
   * @return array
   *   Array of strings.
   */
  protected function getExcludedPaths() {
    $excluded_paths = explode(PHP_EOL, $this->config->get('shutdown_excluded_paths'));
    $redirect_page = $this->config->get('shutdown_redirect_page');

    // Let other modules modify the list of excluded paths.
    $this->moduleHandler->alter('shutdown_excluded_paths', $excluded_paths);
    // Add the redirect page to excluded paths to avoid infinite loops.
    $excluded_paths[] = $redirect_page;
    return $excluded_paths;
  }

  /**
   * Get a list of route names to exclude from shutdown.
   *
   * @return array
   *   Array of strings keyed by route name.
   */
  protected function getExcludedRouteNames() {
    // Define exluded route names.
    $excluded_route_names = [];
    // Let other modules add additional route names.
    $this->moduleHandler->alter('shutdown_excluded_route_names', $excluded_route_names);
    // User login and password recovery pages should always be accessible.
    $excluded_route_names += [
      'user.login' => 'user.login',
      'user.logout' => 'user.logout',
      'user.pass' => 'user.pass',
      'user.reset.login' => 'user.reset.login',
    ];
    // If http cron is allowed, we add the cron route name.
    if ($this->config->get('shutdown_allow_http_cron')) {
      $excluded_route_names += ['system.cron' => 'system.cron'];
    }
    return $excluded_route_names;
  }

  /**
   * Checks if a path matches excluded paths.
   *
   * @param string $path
   *
   * @return bool
   */
  protected function isExcludedPath($path) {

    return (bool) array_filter($this->getExcludedPaths(), function ($excluded_path) use ($path) {
      return \Drupal::service('path.matcher')->matchPath($path, $excluded_path);
    });
  }

}
