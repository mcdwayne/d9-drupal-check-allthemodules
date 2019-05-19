<?php

namespace Drupal\webfactory_master\SiteDeploy;

/**
 * Interface SiteInstaller.
 *
 * @package Drupal\webfactory_master\SiteDeploy
 */
interface SiteInstallerInterface {

  /**
   * Let the opportunity for installer to prepare install.
   *
   * It's a good place to prepare filesystem or database before install.
   */
  public function prepare();

  /**
   * Launch install process.
   */
  public function install();

}
