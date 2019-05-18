<?php
declare(strict_types=1);

namespace Drupal\membership_entity\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a MemberId annotation object.
 *
 * Plugin Namespace: Plugin\MemberId
 *
 * For a working example, see \Drupal\membership_entity\Plugin\NumericMemberId
 *
 * @see plugin_api
 * @see hook_member_id_info_alter()
 *
 * @Annotation
 */
class MemberId extends Plugin {

  /**
   * The MemberId plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the MemberId plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The description of the MemberId plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

}
