<?php

namespace Drupal\twitter_embed\Plugin\Block;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\twitter_embed\TwitterButtonWidget;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'TwitterButtonBlock' block.
 *
 * @Block(
 *  id = "twitter_embed_button",
 *  admin_label = @Translation("Twitter Button"),
 * )
 */
class TwitterButtonBlock extends TwitterBlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('twitter_embed.button_widget')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return TwitterButtonWidget::getDefaultSettings()
    + parent::defaultConfiguration();
  }

}
