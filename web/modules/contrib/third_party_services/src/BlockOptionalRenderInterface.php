<?php

namespace Drupal\third_party_services;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Spoof the markup of a block when third-party is restricted from usage.
 */
interface BlockOptionalRenderInterface extends ContainerInjectionInterface {

  /**
   * BlockOptionalRender constructor.
   *
   * @param MediatorInterface $mediator
   *   Instance of the "MODULE.mediator" service.
   */
  public function __construct(MediatorInterface $mediator);

  /**
   * Validates third-party service availability.
   *
   * @param array $element
   *   Renderable array of block with main content.
   * @param \Drupal\Core\Block\BlockPluginInterface $block
   *   An instance of the block.
   */
  public function process(array &$element, BlockPluginInterface $block);

}
