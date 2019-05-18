<?php

namespace Drupal\block_style_plugins\EventSubscriber;

use Drupal\block_style_plugins\Plugin\BlockStyleManager;
use Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent;
use Drupal\layout_builder\LayoutBuilderEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds Layout Builder component styles.
 */
class SectionComponentStyles implements EventSubscriberInterface {

  /**
   * The Block Styles Manager.
   *
   * @var \Drupal\layout_builder\Plugin\BlockStyles\BlockStylesManager
   */
  protected $blockStyleManager;

  /**
   * Creates a SectionComponentStyles object.
   *
   * @param \Drupal\block_style_plugins\Plugin\BlockStyleManager $blockStyleManager
   *   The Block Style Manager.
   */
  public function __construct(BlockStyleManager $blockStyleManager) {
    $this->blockStyleManager = $blockStyleManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];

    // Skip this if the Layout Builder is not installed.
    if (class_exists('\Drupal\layout_builder\LayoutBuilderEvents')) {
      $events[LayoutBuilderEvents::SECTION_COMPONENT_BUILD_RENDER_ARRAY] = 'onBuildRender';
    }

    return $events;
  }

  /**
   * Add styles to a section component.
   *
   * @param \Drupal\layout_builder\Event\SectionComponentBuildRenderArrayEvent $event
   *   The section component build render array event.
   */
  public function onBuildRender(SectionComponentBuildRenderArrayEvent $event) {
    $block_styles = $event->getComponent()->getThirdPartySettings('block_style_plugins');

    if ($block_styles) {
      $build = $event->getBuild();

      // Look for all available plugins.
      $available_plugins = $this->blockStyleManager->getDefinitions();

      foreach ($block_styles as $plugin_id => $configuration) {
        // Only instantiate plugins that are available.
        if (array_key_exists($plugin_id, $available_plugins)) {
          /** @var \Drupal\layout_builder\Plugin\BlockStyles\BlockStylesInterface $plugin */
          $plugin = $this->blockStyleManager->createInstance($plugin_id, $configuration);
          $build = $plugin->build($build);
        }
      }

      $event->setBuild($build);
    }
  }

}
