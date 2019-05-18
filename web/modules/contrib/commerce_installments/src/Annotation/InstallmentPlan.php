<?php

namespace Drupal\commerce_installments\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Installment Plan item annotation object.
 *
 * @see \Drupal\commerce_installments\Plugin\InstallmentPlanMethodManager
 * @see plugin_api
 *
 * @Annotation
 */
class InstallmentPlan extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
