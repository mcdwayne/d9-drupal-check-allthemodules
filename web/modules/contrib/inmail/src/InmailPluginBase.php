<?php

namespace Drupal\inmail;

use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for Inmail plugins - deliverers, analyzers, handlers.
 */
abstract class InmailPluginBase extends PluginBase implements PluginRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public static function checkPluginRequirements() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function checkInstanceRequirements() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isAvailable() {
    return TRUE;
  }

}
