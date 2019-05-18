<?php

namespace Drupal\prod_check\Plugin\ProdCheck\Modules;


/**
 * Devel generate check
 *
 * @ProdCheck(
 *   id = "devel_generate",
 *   title = @Translation("Devel generate"),
 *   category = "modules",
 *   provider = "devel_generate"
 * )
 */
class DevelGenerate extends ModulesBase {

  /**
   * {@inheritdoc}
   */
  public function init() {
    $this->module = 'devel_generate';
    $this->routeName = 'devel.admin_settings';
  }

}
