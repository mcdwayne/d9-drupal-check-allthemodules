<?php

namespace Drupal\prod_check\Plugin\ProdCheck\Modules;


/**
 * Devel status check
 *
 * @ProdCheck(
 *   id = "devel",
 *   title = @Translation("Devel"),
 *   category = "modules",
 *   provider = "devel"
 * )
 */
class Devel extends ModulesBase {

  /**
   * {@inheritdoc}
   */
  public function init() {
    $this->module = 'devel';
    $this->routeName = 'devel.admin_settings';
  }

}
