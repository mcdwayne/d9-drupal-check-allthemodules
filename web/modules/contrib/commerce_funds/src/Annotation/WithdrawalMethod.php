<?php

namespace Drupal\commerce_funds\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a withdrawal method item annotation object.
 *
 * Plugin Namespace: Plugin\commerce_funds\WithdrawalMethod.
 *
 * @see \Drupal\commerce_funds\Plugin\WithdrawalMethodManager
 * @see plugin_api
 *
 * @Annotation
 */
class WithdrawalMethod extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the flavor.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

}
