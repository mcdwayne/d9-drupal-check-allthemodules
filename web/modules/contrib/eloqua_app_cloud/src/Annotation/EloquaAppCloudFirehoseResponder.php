<?php

namespace Drupal\eloqua_app_cloud\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Eloqua AppCloud Firehose Responder item annotation object.
 *
 * @see \Drupal\eloqua_app_cloud\Plugin\EloquaAppCloudFirehoseResponderManager
 * @see plugin_api
 *
 * @Annotation
 */
class EloquaAppCloudFirehoseResponder extends Plugin {


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
