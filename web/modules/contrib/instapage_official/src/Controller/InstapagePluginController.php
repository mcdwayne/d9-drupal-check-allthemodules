<?php

/**
 * @file
 * Contains \Drupal\instapage_cms_plugin\Controller\InstapagePluginController.
 */

namespace Drupal\instapage_cms_plugin\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Base plugin controller.
 */
class InstapagePluginController extends ControllerBase {

  public function content()
  {

    ob_start();
    \InstapageCmsPluginConnector::getSelectedConnector()->loadPluginDashboard();
    $content = ob_get_contents();
    ob_end_clean();

    $build = array(
      '#markup' => $content,
      '#attached' => array(
        'library' =>  array(
          'instapage_cms_plugin/instapage_cms_plugin_library'
        )
      )
    );

    return $build;
  }
}
