<?php

namespace Drupal\weibo_widget\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Config\ConfigFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a 'Weibo Like Button' block.
 *
 * @Block(
 *   id = "weibo_like_button_block",
 *   admin_label = @Translation("Weibo Like Button"),
 *   category = @Translation("Social")
 * )
 */
class WeiboLikeButtonBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $config;

  /**
   * Class constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactory $config) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Getting module configurations.
    $config = $this->config->get('weibo_widget.settings');
    $appkey = $config->get('weibo_widget_appkey');

    // Setting Weibo tag attributes.
    $attributes = new Attribute();
    $attributes['appkey'] = $appkey;

    // Settting WBML tag.
    $wbml = $this->t('No AppKey configured.');
    if ($appkey) {
      $wbml = "<wb:like {$attributes}></wb:like>";
    }

    return [
      '#theme' => 'weibo_like',
      '#wbml' => $wbml,
      '#attached' => [
        'library' => [
          'weibo_widget/weibo_api',
        ],
      ],
    ];
  }

}
