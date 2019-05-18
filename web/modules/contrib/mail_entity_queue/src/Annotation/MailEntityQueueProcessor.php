<?php

namespace Drupal\mail_entity_queue\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a "Mail Queue Processor" annotation object.
 *
 * @Annotation
 */
class MailEntityQueueProcessor extends Plugin {

  /**
   * The plugin ID of the mail queue processor.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the mail queue processor.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the mail queue processor.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

}
