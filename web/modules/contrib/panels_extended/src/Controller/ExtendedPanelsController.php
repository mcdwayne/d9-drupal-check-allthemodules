<?php

namespace Drupal\panels_extended\Controller;

use Drupal\panels\Controller\Panels;
use Drupal\panels_extended\Plugin\DisplayBuilder\ExtendedDisplayBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides extra route controllers for the extended panels routes.
 */
class ExtendedPanelsController extends Panels {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('panels_extended.plugin.manager.block'),
      $container->get('plugin.manager.condition'),
      $container->get('plugin.manager.display_variant'),
      $container->get('context.handler'),
      $container->get('user.shared_tempstore')
    );
  }

  /**
   * Enable / disable a block.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $tempstore_id
   *   The identifier of the temporary store.
   * @param string $machine_name
   *   The identifier of the block display variant.
   * @param string $block_id
   *   The block uuid.
   * @param bool $setEnabled
   *   TRUE to enable, FALSE to disable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects to the given 'destination' query parameter.
   */
  public function toggleBlock(Request $request = NULL, $tempstore_id = NULL, $machine_name = NULL, $block_id = NULL, $setEnabled = TRUE) {
    $cached_values = $this->getCachedValues($this->tempstore, $tempstore_id, $machine_name);

    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $variant_plugin */
    $variant_plugin = $cached_values['plugin'];
    $block = $variant_plugin->getBlock($block_id);

    $block->setConfigurationValue(ExtendedDisplayBuilder::BLOCK_CONFIG_DISABLED, !$setEnabled);
    $variant_plugin->updateBlock($block_id, $block->getConfiguration());

    // PageManager specific handling.
    if (isset($cached_values['page_variant'])) {
      /** @var \Drupal\page_manager\PageVariantInterface $page_variant */
      $page_variant = $cached_values['page_variant'];
      $page_variant->getVariantPlugin()->setConfiguration($variant_plugin->getConfiguration());
    }

    $this->tempstore->get($tempstore_id)->set($cached_values['id'], $cached_values);

    $uri = $request->query->get('destination');
    return new RedirectResponse($uri);
  }

}
