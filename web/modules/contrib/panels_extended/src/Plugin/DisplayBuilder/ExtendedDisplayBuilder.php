<?php

namespace Drupal\panels_extended\Plugin\DisplayBuilder;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\panels\Plugin\DisplayBuilder\StandardDisplayBuilder;
use Drupal\panels_extended\BlockConfig\VisibilityInterface;
use Drupal\panels_extended\Form\ExtendedPanelsContentForm;
use Drupal\panels_extended\Form\PanelsScheduleBlockForm;
use Drupal\panels_extended\Event\ExtendedDisplayBuilderEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a display builder with extended features.
 *
 * @DisplayBuilder(
 *   id = "panels_extended",
 *   label = @Translation("Extended")
 * )
 */
class ExtendedDisplayBuilder extends StandardDisplayBuilder {

  /**
   * Block configuration value for disabled flag.
   */
  const BLOCK_CONFIG_DISABLED = 'extended_disabled';

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ContextHandlerInterface $context_handler,
    AccountInterface $account,
    ModuleHandlerInterface $module_handler,
    EventDispatcherInterface $event_dispatcher
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $context_handler, $account, $module_handler);

    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('context.handler'),
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Removes blocks from the regions which are not enabled or not visible.
   *
   * @param array $regions
   *   The render array representing regions.
   *
   * @return array
   *   An associative array, keyed by region ID, containing the render arrays
   *   representing the content of each region with visible blocks only.
   */
  public function filterVisibleBlocks(array $regions) {
    $visible = [];
    foreach ($regions as $region => $blocks) {
      $visible[$region] = [];
      if (!$blocks) {
        continue;
      }

      /** @var \Drupal\Core\Block\BlockPluginInterface[] $blocks */
      foreach ($blocks as $block_id => $block) {
        $blockConfiguration = $block->getConfiguration();
        if (!empty($blockConfiguration[self::BLOCK_CONFIG_DISABLED]) ||
          !$this->blockIsScheduled($blockConfiguration) ||
          ($block instanceof VisibilityInterface && !$block->isVisible())
        ) {
          continue;
        }
        $visible[$region][$block_id] = $block;
      }
    }
    return $visible;
  }

  /**
   * Is the block visible based on the scheduling configuration?
   *
   * @param array $blockConfiguration
   *   The block configuration.
   *
   * @return bool
   *   FALSE if the block is unscheduled, TRUE otherwise.
   */
  public function blockIsScheduled(array $blockConfiguration) {
    $isVisible = TRUE;
    if (!empty($blockConfiguration[PanelsScheduleBlockForm::CFG_START])) {
      $isVisible = $isVisible && time() >= $blockConfiguration[PanelsScheduleBlockForm::CFG_START];
    }
    if (!empty($blockConfiguration[PanelsScheduleBlockForm::CFG_END])) {
      $isVisible = $isVisible && time() <= $blockConfiguration[PanelsScheduleBlockForm::CFG_END];
    }
    return $isVisible;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildRegions(array $regions, array $contexts) {
    $filteredRegions = $this->filterVisibleBlocks($regions);
    $this->dispatchPrebuildRegionsEvent($filteredRegions);

    return parent::buildRegions($filteredRegions, $contexts);
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardOperations($cached_values) {
    $operations = parent::getWizardOperations($cached_values);
    if (isset($operations['content']['form'])) {
      $operations['content']['form'] = ExtendedPanelsContentForm::class;
    }
    return $operations;
  }

  /**
   * Dispatch the ExtendedDisplayBuilderEvent::PREBUILD_REGIONS event.
   *
   * @param array $regions
   *   The regions to be built.
   */
  protected function dispatchPrebuildRegionsEvent(array &$regions) {
    $event = new ExtendedDisplayBuilderEvent($regions);
    $this->eventDispatcher->dispatch(ExtendedDisplayBuilderEvent::PREBUILD_REGIONS, $event);
  }

}
