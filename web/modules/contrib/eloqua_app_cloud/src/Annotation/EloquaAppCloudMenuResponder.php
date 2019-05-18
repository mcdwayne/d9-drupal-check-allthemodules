<?php

namespace Drupal\eloqua_app_cloud\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Eloqua AppCloud Menu Responder item annotation object.
 *
 * @see \Drupal\eloqua_app_cloud\Plugin\EloquaAppCloudMenuResponderManager
 * @see plugin_api
 *
 * @Annotation
 */
class EloquaAppCloudMenuResponder extends Plugin {


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
