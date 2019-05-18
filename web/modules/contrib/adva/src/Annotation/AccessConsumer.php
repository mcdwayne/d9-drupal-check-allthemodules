<?php

namespace Drupal\adva\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an annotation object for access consumers.
 *
 * Plugin Namespace: Plugin\adva\AccessConsumer.
 *
 * @see plugin_api
 *
 * @Annotation
 */
class AccessConsumer extends Plugin {

  /**
   * Name of the entity type the consumer enables Advanced Access for.
   *
   * @var string
   */
  private $entityType;

}
