<?php

namespace Drupal\jsld\Plugin\jsld;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * {@inheritdoc}
 */
interface JsldPluginInterface extends PluginInspectionInterface {

  /**
   * Return plugin ID.
   */
  public function getId();

  /**
   * Plugin status.
   *
   * @return bool
   *   Plugin status. By default all plugin are enabled, but some can be
   *   disabled for many reasons.
   */
  public function isEnabled();

  /**
   * Build result array with structured data.
   *
   * @return array
   *   An array with JSON-LD structured data.
   */
  public function build();

  /**
   * Returns array with configurations.
   *
   * @return array
   *   An array with plugin configs.
   */
  public function getConfiguration();

}
