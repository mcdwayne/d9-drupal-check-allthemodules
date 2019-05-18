<?php

namespace Drupal\Tests\panels_everywhere\Functional;

use Drupal\page_manager\Entity\PageVariant;
use Drupal\Tests\BrowserTestBase;

/**
 * This class simplifies the setup of functional tests.
 *
 * @group panels_everywhere
 */
abstract class PanelsEverywhereBrowserTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'panels_everywhere',
  ];

  /**
   * The page entity storage handler.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $pageStorage;

  /**
   * The page_variant entity storage handler.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $pageVariantStorage;

  /**
   * The block plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $blockManager;

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $conditionManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->pageStorage = \Drupal::entityTypeManager()->getStorage('page');
    $this->pageVariantStorage = \Drupal::entityTypeManager()->getStorage('page_variant');
    $this->blockManager = \Drupal::service('plugin.manager.block');
    $this->conditionManager = \Drupal::service('plugin.manager.condition');
  }

  /**
   * Place a block on the given Variant entity.
   *
   * @param \Drupal\page_manager\Entity\PageVariant $variant
   *   The variant entity.
   * @param string $plugin_id
   *   The plugin id of the block.
   * @param string $region
   *   The region to place the block into.
   * @param array $additional_config
   *   [optional] Additional block configuration.
   */
  protected function placeBlockOnVariant(PageVariant $variant, $plugin_id, $region, array $additional_config = []) {
    $blockConfiguration = [
        'region' => $region,
      ] + $additional_config;
    $variantPlugin = $variant->getVariantPlugin();

    $blockInstance = $this->blockManager
      ->createInstance($plugin_id, $blockConfiguration);

    $variantPlugin->addBlock($blockInstance->getConfiguration());
  }

  /**
   * Adds a request_path condition to the variant with the given configuration.
   *
   * @param \Drupal\page_manager\Entity\PageVariant $variant
   *   The variant entity.
   * @param string $paths
   *   The list of paths separated by newline.
   * @param bool $negated
   *   Whether to negate the path selection.
   */
  protected function addPathCondition(PageVariant $variant, $paths, $negated = FALSE) {
    $conditionInstance = $this->conditionManager->createInstance('request_path', [
      'pages' => $paths,
      'negate' => $negated,
    ]);
    $variant->addSelectionCondition($conditionInstance->getConfiguration());
  }

}