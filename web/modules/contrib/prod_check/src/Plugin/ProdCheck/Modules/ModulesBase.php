<?php

namespace Drupal\prod_check\Plugin\ProdCheck\Modules;

use Drupal\prod_check\Plugin\ProdCheck\ProdCheckBase;

/**
 * Generic module check class
 */
abstract class ModulesBase extends ProdCheckBase {

  /**
   * The module to check
   */
  protected $module = '';

  /**
   * The route name of the module
   */
  protected $routeName = '';

  /**
   * {@inheritdoc}
   */
  public function state() {
    return !$this->moduleHandler->moduleExists($this->module);
  }

  /**
   * {@inheritdoc}
   */
  public function successMessages() {
    return [
      'value' => $this->t('Disabled'),
      'description' => $this->t('Your settings are OK for production use.'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function failMessages() {
    return [
      'value' => $this->t('Enabled'),
      'description' => $this->generateDescription(
        $this->title(),
        $this->routeName,
        'You have enabled the %link module. This should not be running on a production environment!'
      ),
    ];
  }

}
