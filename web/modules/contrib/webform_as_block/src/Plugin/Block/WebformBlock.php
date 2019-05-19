<?php

namespace Drupal\webform_as_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'WebformBlock' block.
 *
 * @Block(
 *  id = "webform_block",
 *  admin_label = @Translation("Webform block"),
 *  deriver = "Drupal\webform_as_block\Plugin\Derivative\WebformBlock",
 * )
 */
class WebformBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block_id = $this->getDerivativeId();
    $build = [];
    $build[$block_id] = [
      '#type' => 'webform',
      '#webform' => $block_id,
    ];

    return $build;
  }
}
