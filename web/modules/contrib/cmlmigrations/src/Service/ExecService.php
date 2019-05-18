<?php

namespace Drupal\cmlmigrations\Service;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Exec Service.
 */
class ExecService implements ExecServiceInterface {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates a new CmlService manager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Exec.
   */
  public function exec($nohup = TRUE, $update = FALSE) {
    $drush = $this->getDrush();
    $root = DRUPAL_ROOT;
    $cmd = "{$drush} mim --group=cml --root=$root";
    if ($update) {
      $cmd .= " --update";
    }
    if ($nohup) {
      $cmd = "nohup $cmd > ~/nohup.out 2> ~/nohup.err < /dev/null &";
    }
    $result = "<b>\$ $cmd</b>\n";
    $result .= shell_exec($cmd);
    return $result;
  }

  /**
   * Exec test.
   */
  public function execTest() {
    $cmd = "whoami";
    $result .= "<b>\$ $cmd</b>\n";
    $result .= shell_exec($cmd);
    return $result;
  }

  /**
   * Drush test.
   */
  public function drushTest() {
    $drush = $this->getDrush();
    $cmd = "{$drush} --version";
    $result .= "<b>\$ $cmd</b>\n";
    $result .= shell_exec($cmd);
    return $result;
  }

  /**
   * Drush test.
   */
  public function nohupTest() {
    $drush = $this->getDrush();
    $cmd = "nohup --version";
    $result .= "<b>\$ $cmd</b>\n";
    $result .= shell_exec($cmd);
    return $result;
  }

  /**
   * Drush.
   */
  public function getDrush() {
    $config = $this->configFactory->get('cmlmigrations.settings');
    $drush = "/usr/local/bin/drush";
    if ($config->get('drush')) {
      $drush = $config->get('drush');
    }
    return $drush;
  }

}
