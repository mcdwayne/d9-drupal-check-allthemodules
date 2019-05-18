<?php

namespace Drupal\webpay\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Webpay commerce system item annotation object.
 *
 * @see \Drupal\webpay\Plugin\WebpayCommerceSystemManager
 * @see plugin_api
 *
 * @Annotation
 */
class WebpayCommerceSystem extends Plugin {


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
