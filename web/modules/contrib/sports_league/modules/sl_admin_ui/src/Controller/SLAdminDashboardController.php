<?php

namespace Drupal\sl_admin_ui\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * The dashboard controller.
 */
class SLAdminDashboardController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
    $plugin_manager = \Drupal::service('plugin.manager.sl_admin_ui_widgets');
    $form_plugins = $plugin_manager->getDefinitions();
    $widgets = [];
    foreach ($form_plugins as $name => $plugin) {
      $widgets[$name] = $plugin_manager->createInstance($name)->content();
    }

    $build = array(
      '#theme' => 'sl_admin_ui_dashboard',
      '#widgets' => $widgets,
    );
    return $build;
  }

}