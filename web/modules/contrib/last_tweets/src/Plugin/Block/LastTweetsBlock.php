<?php

namespace Drupal\last_tweets\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\last_tweets\Service\LastTweetsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'LastTweetsBlock' block.
 *
 * @Block(
 *  id = "last_tweets_block",
 *  admin_label = @Translation("Last tweets block"),
 * )
 */
class LastTweetsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Lastweet manager.
   *
   * @var \Drupal\last_tweets\Service\LastTweetsManager
   */
  protected $lastTweetsManager;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   Configuration array.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\last_tweets\Service\LastTweetsManager $lastTweetsManager
   *   LastTweets manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    LastTweetsManager $lastTweetsManager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->lastTweetsManager = $lastTweetsManager;
  }

  /**
   * Create.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   *
   * @return static
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('last_tweets.service.last_tweets_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['twitter_limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter limit'),
      '#description' => $this->t('Set the number of tweets. Default = 3.'),
      '#default_value' => isset($this->configuration['twitter_limit']) ? $this->configuration['twitter_limit'] : 3,
    ];
    $form['user_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter username'),
      '#description' => $this->t('You can override the username for this block.'),
      '#default_value' => isset($this->configuration['user_name']) ? $this->configuration['user_name'] : '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['twitter_limit'] = $form_state->getValue('twitter_limit');
    $this->configuration['user_name'] = $form_state->getValue('user_name');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $twitter_limit = $this->configuration['twitter_limit'];
    $user_name = $this->configuration['user_name'];
    $tweets = $this->lastTweetsManager->getTweets($user_name, $twitter_limit);

    $build = [];
    $build['last_tweets'] = $tweets;
    $build['#attached'] = [
      'library' => [
        'last_tweets/last-tweets',
      ],
    ];
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
