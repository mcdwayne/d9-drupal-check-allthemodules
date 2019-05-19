<?php

namespace Drupal\smart_content\DecisionAgent;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Smart decision agent plugins.
 */
interface DecisionAgentInterface extends PluginInspectionInterface {

  /**
   * Returns placeholder for associated javascript to search for.
   *
   * @param array $context
   *
   * @return mixed
   */
  public function renderPlaceholder($context);

  /**
   * Returns required JS libraries for this type.
   *
   * @return mixed
   */
  public function getLibraries();

}
