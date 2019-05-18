<?php

namespace Drupal\certificatelogin\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Certification Authority Signature Verification item annotation object.
 *
 * @see \Drupal\certificatelogin\Plugin\CaSignatureVerificationPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class CaSignatureVerificationPlugin extends Plugin {


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
