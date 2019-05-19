<?php
/**
 * @file
 * Contains \Drupal\sl_admin_ui\SLAdminUIWidgetPluginInterface
 */
namespace Drupal\sl_stats;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
/**
 * Defines an interface for reusable form plugins.
 */
interface SLStatsComputerPluginInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {
  /**
   * Return the name of the reusable form plugin.
   *
   * @return string
   */
  public function getName();



}
