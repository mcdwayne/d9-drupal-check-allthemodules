<?php

namespace Drupal\twitter_trends\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\twitter_trends\Services\TwitterTrends;

/**
 * Provides a 'TwitterTrendsBlock' block.
 *
 * @Block(
 *  id = "twitter_trends_block",
 *  admin_label = @Translation("Twitter Trends"),
 * )
 */
class TwitterTrendsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The TwitterTrends service variable.
   *
   * @var \Drupal\twitter_trends\Services\TwitterTrends
   */
  private $trends;

  /**
   * Constructs TwitterTrends & plugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\twitter_trends\Services\TwitterTrends $trends
   *   TwitterTrends Service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TwitterTrends $trends) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->trends = $trends;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('twitter_trends.abraham_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();
    $options = [
      '5' => 5,
      '10' => 10,
      '15' => 15,
      '20' => 20,
      '25' => 25,
      '30' => 30,
      '40' => 40,
      '45' => 45,
      '50' => 50,
    ];
    $form['total_tweet'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of Trends'),
      '#options' => $options,
      '#description' => $this->t('Total Number of Trends to display in block.'),
      '#default_value' => isset($config['total_tweet']) ? $config['total_tweet'] : '10',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $build = [];
    $build['#theme'] = 'twitter_trends';
    $build['#trends'] = $this->trends->fetchData($config['total_tweet']);
    $build['#attached']['library'][] = 'twitter_trends/twitter_trends.tweets';
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('total_tweet', $form_state->getValue('total_tweet'));
  }

}
