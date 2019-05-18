<?php

namespace Drupal\pc;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use PhpConsole\Connector;
use PhpConsole\Handler;
use PhpConsole\Storage\File;

/**
 * PhpConsole connector factory.
 */
class ConnectorFactory {

  /**
   * Connector.
   *
   * @var \PhpConsole\Connector
   */
  protected static $connector;

  /**
   * Constructs a connector factory object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory object to use.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $account) {
    // PhpConsole library does not allow us to set postponed storage after
    // connector is instanced and there is no way to check if it is already set.
    // It might cause an error when the service is being rebuilt. So we have to
    // stick to the same connector instance throughout.
    // @see Connector::setPostponeStorage@see()
    if (self::$connector || !$account->hasPermission('view debug information')) {
      return;
    }

    // PhpConsole library is not installed.
    if (!class_exists('PhpConsole\Connector')) {
      return;
    }

    $pc_data_file = $config_factory
      ->get('system.file')
      ->get('path.temporary') . '/pc.data';

    Connector::setPostponeStorage(new File($pc_data_file, FALSE));

    self::$connector = Connector::getInstance();

    $settings = $config_factory->get('pc.settings');

    self::$connector->getDumper()->levelLimit = $settings->get('dumper_maximum_depth');

    if ($settings->get('password_enabled')) {
      $password = $settings->get('password');
      self::$connector->setPassword($password ?: user_password());
    }

    $ips = explode("\n", $settings->get('ips'));
    $ips = array_map('trim', $ips);
    $ips = array_filter($ips, 'strlen');
    $ips && self::$connector->setAllowedIpMasks($ips);

    // Configure eval provider.
    if ($settings->get('password_enabled') && $settings->get('remote_php_execution') && $account->hasPermission('execute remote php code')) {
      $eval_provider = self::$connector->getEvalDispatcher()->getEvalProvider();
      $eval_provider->setOpenBaseDirs([DRUPAL_ROOT]);
      self::$connector->startEvalRequestsListener();
    }

    // Enable error handler.
    if ($settings->get('track_errors')) {
      $handler = Handler::getInstance();
      if (!$handler->isStarted()) {
        $handler->start();
        self::$connector->setSourcesBasePath(DRUPAL_ROOT);
      }
    }
  }

  /**
   * Returns PHP Console connector.
   */
  public function get() {
    return self::$connector;
  }

}
