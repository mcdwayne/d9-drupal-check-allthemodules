<?php
/**
 * @file
 * Contains \Drupal\sl_admin_ui\Annotation\SLAdminUIWidget.
 */
namespace Drupal\sl_stats\Annotation;
use Drupal\Component\Annotation\Plugin;

/**
 * Defines a SL Stats Computer plugin annotation object.
 *
 * @Annotation
 */
class SLStatsComputer extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;
  /**
   * The name of the widget.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;
  /**
   * The bundle with this plugin
   *
   * It must implement \Drupal\reusable_forms\Form\ReusableFormInterface.
   *
   * @var string
   */
  public $bundle;
}