<?php

/**
 * @file
 * This file is part of Drupal Webfactory.
 *
 * As a Drupal install process is a complex task (that loads a new Kernel,
 * etc.), this a convenient way to launch an install through an HTTP POST
 * request.
 *
 * @see \Drupal\webfactory_master\Form\SatelliteEntityDeployForm
 */

use Drupal\Core\DrupalKernel;
use Drupal\webfactory_master\Entity\SatelliteEntity;
use Drupal\webfactory_master\SiteDeploy\Installer\NativeInstaller;
use Symfony\Component\HttpFoundation\Request;

$autoloader = require_once 'autoload.php';

if (isset($_POST['sat_id']) && isset($_POST['token'])) {

  @ignore_user_abort(TRUE);

  @set_time_limit(240);

  /*
   * We bootstrap webfactory master to retrieve information about
   * given satellite.
   */
  webfactory_boot_kernel($autoloader);

  $info = \Drupal::state()->get($_POST['sat_id'] . '.install_info');
  if (!empty($info) && $info['token'] == $_POST['token']) {

    $sat = SatelliteEntity::load($_POST['sat_id']);

    $native_installer = new NativeInstaller($sat, $info['db_info']);
    $native_installer->prepare();

    \Drupal::state()->delete($_POST['sat_id'] . '.install_info');

    $native_installer->install();
  }
}

/**
 * Bootstrap Drupal default site (webfactory master).
 *
 * @param mixed $autoloader
 *   Autoloader.
 */
function webfactory_boot_kernel($autoloader) {
  $request = Request::createFromGlobals();
  $kernel = DrupalKernel::createFromRequest(
    $request,
    $autoloader,
    'prod'
  );
  $kernel->loadLegacyIncludes();
  $kernel->boot();
}
