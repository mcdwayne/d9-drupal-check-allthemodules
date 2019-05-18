<?php

namespace Drupal\custom_db_table_views\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * An settings example controller.
 */
class SettingsCustomController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  public function content() {
    $build = ['#type' => 'markup', '#markup' => 'Latest Feature coming soon....'];
    return $build;
  }

}
