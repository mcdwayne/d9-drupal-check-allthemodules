<?php

/**
 * @file
 * Contains \Drupal\Core\Field\Annotation\FieldFormatter.
 */

namespace Drupal\cronpub\Plugin\Cronpub;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a CronpubAction annotation object.
 *
 * A CronpubAction handles the actions of the cronpub module on an entity.
 * They are invoced by the CronpubExecutionService during a cronjob.
 *
 * @Annotation
 *
 * @see \Drupal\cronpub\CronpubActionManager
 *
 * @ingroup cronpub_action
 */
class CronpubAction extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the cronpub action.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * A short description of the cronpub action.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * Field label and description for start date.
   *
   * @ingroup plugin_translatable
   *
   * @var array
   */
  public $start;

  /**
   * Field label and description for start date.
   *
   * @ingroup plugin_translatable
   *
   * @var array
   */
  public $end;

  /**
   * The permissions required to use the plugin.
   *
   * @var string
   */
  public $permission = '';

  /**
   * The name of the cronpub action class.
   *
   * This is not provided manually, it will be added by the discovery mechanism.
   *
   * @var string
   */
  public $class;

}
