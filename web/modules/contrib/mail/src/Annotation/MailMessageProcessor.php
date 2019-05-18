<?php

namespace Drupal\mail\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Mail message processor item annotation object.
 *
 * @see \Drupal\mail\MailMessageProcessorManager
 * @see plugin_api
 *
 * @Annotation
 */
class MailMessageProcessor extends Plugin {

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
