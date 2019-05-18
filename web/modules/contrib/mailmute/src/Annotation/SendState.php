<?php
/**
 * @file
 * Contains \Drupal\mailmute\Annotation\SendState.
 */

namespace Drupal\mailmute\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation for the definition of a send state.
 *
 * @ingroup plugin
 *
 * @Annotation
 */
class SendState extends Plugin {

  /**
   * The unique, machine-readable name of this state.
   *
   * @var string
   */
  public $id;

  /**
   * The translated, human-readable name of this state.
   *
   * @var string
   */
  public $label;

  /**
   * A brief description of this state.
   *
   * @var string
   */
  public $description;

  /**
   * Whether to suppress messages to addresses with this state.
   *
   * @var bool
   */
  public $mute;

  /**
   * Whether an administrative permission is required to set the state manually.
   *
   * @var bool
   */
  public $admin;

  /**
   * The plugin id of the parent status.
   *
   * @var string
   */
  public $parent_id;

}
