<?php

namespace Drupal\pc;

use Drupal\Component\Utility\Timer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * PHP Console event subscriber.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * Current logged in user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * PHP Console settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * Database logger.
   *
   * @var \Drupal\Core\Database\Log
   */
  protected $databaseLogger;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Debug info.
   *
   * @var array
   */
  protected $debugInfo;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current logged in user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Configuration object factory.
   * @param \Drupal\Core\Database\Connection $db_connection
   *   Database connection.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\pc\ConnectorFactory $connector_factory
   *   Connector factory.
   */
  public function __construct(AccountInterface $current_user, ConfigFactoryInterface $config_factory, Connection $db_connection, RouteMatchInterface $route_match, ConnectorFactory $connector_factory) {
    // Sometimes we need to build connector explicitly.
    $connector_factory->get();

    $this->currentUser = $current_user;
    $this->settings = $config_factory->get('pc.settings');
    $this->databaseLogger = $db_connection->getLogger();
    $this->routeMatch = $route_match;
    $this->debugInfo = $this->settings->get('debug_info');
  }

  /**
   * Kernel request event handler.
   */
  public function onKernelRequest() {
    $this->debugInfo['server'] && pc($_SERVER, 'Server');
    $this->debugInfo['session'] && pc($_SESSION, 'Session');
    $this->debugInfo['cookie'] && pc($_COOKIE, 'Cookie');
    $this->debugInfo['post'] && pc($_POST, 'Post');
    $this->debugInfo['get'] && pc($_GET, 'Get');
    $this->debugInfo['logged_user'] && pc($this->currentUser, 'Logged user');

    if ($this->debugInfo['route']) {
      // Prevent user object from being modified.
      // See https://www.drupal.org/node/2752825.
      $parameters = $this->routeMatch->getParameters();
      if ($parameters->has('user')) {
        $user = $parameters->get('user');
        $parameters->remove('user');
        $parameters->set('user', $user);
      }

      $route_info = [
        'name' => $this->routeMatch->getRouteName(),
        'object' => $this->routeMatch->getRouteObject(),
        'parameters' => $parameters,
        'row_parameters' => $this->routeMatch->getRawParameters(),
      ];
      pc($route_info, 'Route');
    }
  }

  /**
   * Kernel response event handler.
   */
  public function onKernelTerminate() {
    $this->debugInfo['execution_time'] && pc(Timer::read('pc_page') . ' ms', 'Execution time');
    $this->debugInfo['memory_usage'] && pc(round(memory_get_usage() / 1024 / 1024, 2), 'Memory usage');
    $this->debugInfo['peak_memory_usage'] && pc(round(memory_get_peak_usage(TRUE) / 1024 / 1024, 2), 'Peak memory usage');
    if ($this->debugInfo['db_queries']) {
      $total_queries = count($this->databaseLogger->get('pc'));
      pc($this->databaseLogger->get('pc'), "DB queries ($total_queries)");
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onKernelRequest'],
      KernelEvents::TERMINATE => ['onKernelTerminate'],
    ];
  }

}
