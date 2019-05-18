<?php
/**
 * @file
 * Contains \Drupal\collect\Plugin\collect\Model\DefaultModelPlugin.
 */

namespace Drupal\collect\Plugin\collect\Model;

use Drupal\collect\CollectContainerInterface;
use Drupal\collect\Model\ModelPluginBase;

/**
 * Default plugin that matches URIs with no configured specific model plugin.
 *
 * @Model(
 *   id = "default",
 *   label = @Translation("Default"),
 *   description = @Translation("The Default model plugin has no functionality, and is used only as fallback when a real model plugin is not specified.")
 * )
 */
class DefaultModelPlugin extends ModelPluginBase {

  /**
   * {@inheritdoc}
   */
  public function parse(CollectContainerInterface $collect_container) {
    return $collect_container->getData();
  }

}
