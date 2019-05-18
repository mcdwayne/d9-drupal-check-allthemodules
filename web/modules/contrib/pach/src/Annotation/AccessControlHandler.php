<?php

namespace Drupal\pach\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an annotation object for access control handlers.
 *
 * Plugin Namespace: Plugin\pach.
 *
 * @see \Drupal\pach\AccessControlHandlerManager
 * @see \Drupal\pach\Plugin\AccessControlHandlerInterface
 * @see \Drupal\pach\Plugin\AccessControlHandlerBase
 * @see plugin_api
 *
 * @Annotation
 */
class AccessControlHandler extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin weight.
   *
   * @var int
   */
  public $weight;

  /**
   * Name of the entity type the handler controls access for.
   *
   * @var string
   */
  public $type;

}
