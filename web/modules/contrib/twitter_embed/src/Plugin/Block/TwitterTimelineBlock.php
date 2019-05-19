<?php

namespace Drupal\twitter_embed\Plugin\Block;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\twitter_embed\TwitterTimelineWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'TwitterTimelineBlock' block.
 *
 * @Block(
 *  id = "twitter_embed_timeline",
 *  admin_label = @Translation("Twitter Timeline"),
 * )
 */
class TwitterTimelineBlock extends TwitterBlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('twitter_embed.timeline_widget')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return TwitterTimelineWidget::getDefaultSettings()
    + parent::defaultConfiguration();
  }

}
