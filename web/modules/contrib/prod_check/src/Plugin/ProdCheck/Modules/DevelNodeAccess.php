<?php

namespace Drupal\prod_check\Plugin\ProdCheck\Modules;


/**
 * Devel node access check
 *
 * @ProdCheck(
 *   id = "devel_node_access",
 *   title = @Translation("Devel node access"),
 *   category = "modules",
 *   provider = "devel_node_access"
 * )
 */
class DevelNodeAccess extends ModulesBase {

  /**
   * {@inheritdoc}
   */
  public function init() {
    $this->module = 'devel_node_access';
    $this->routeName = 'devel.admin_settings';
  }

}
