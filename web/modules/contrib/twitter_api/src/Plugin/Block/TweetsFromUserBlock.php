<?php

namespace Drupal\twitter_api\Plugin\Block;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\twitter_api\TwitterApiClientInterface;
use Drupal\twitter_api\TwitterApiEntityExpander;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a simple block showing tweets from a user.
 *
 * This is designed to be an example of an API implementation.
 *
 * @Block(
 *   id = "tweets_from_user",
 *   admin_label = @Translation("Tweets from user"),
 *   category = @Translation("Twitter API")
 * )
 */
class TweetsFromUserBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The twitter api client service.
   *
   * @var \Drupal\twitter_api\TwitterApiClientInterface
   */
  protected $client;

  /**
   * The expander service.
   *
   * @var \Drupal\twitter_api\TwitterApiEntityExpander
   */
  protected $expander;

  /**
   * FacetSearchFormBlock constructor.
   *
   * @param array $configuration
   *   The plugin config.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\twitter_api\TwitterApiClientInterface $client
   *   The twitter api client service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TwitterApiClientInterface $client, TwitterApiEntityExpander $expander) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
    $this->expander = $expander;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('twitter_api.client'),
      $container->get('twitter_api.entity_expander')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function baseConfigurationDefaults() {
    return [
      'screen_name' => '',
      'count' => 3,
      'cache' => 3600,
    ] + parent::baseConfigurationDefaults();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['screen_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter Username'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['screen_name'],
    ];
    $form['count'] = [
      '#type' => 'number',
      '#title' => $this->t('Count'),
      '#description' => $this->t('Number of tweets to display.'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['count'],
    ];
    $form['cache'] = [
      '#type' => 'select',
      '#title' => $this->t('Cache time'),
      '#options' => [
        Cache::PERMANENT => $this->t('Forever'),
        3600 => $this->t('5 minutes'),
        7200 => $this->t('10 minutes'),
      ],
      '#default_value' => $this->configuration['cache'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['screen_name'] = $form_state->getValue('screen_name');
    $this->configuration['count'] = $form_state->getValue('count');
    $this->configuration['cache'] = $form_state->getValue('cache');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $response = $this->client->getTweets([
      'screen_name' => $this->configuration['screen_name'],
      'count' => $this->configuration['count']
    ]);

    // Don't process anything if there's an error returned from the API.
    if (!empty($response['errors'])) {
      return [];
    }

    $tweets = [];
    // TODO: Move all of this into a better place. Preprocessor?
    foreach ($response as $tweet) {
      $tweet_text = $tweet['text'];
      // Replace any urls in the text.
      $urls = $this->expander->expandUrls($tweet);
      foreach ($urls as $replace => $url) {
        $tweet_text = str_replace($replace, $url->toString(), $tweet_text);
      }

      // Remove any image urls in the text so they are themed separately.
      $images = $this->expander->expandImages($tweet);
      foreach ($images as $replace => $image_url) {
        $tweet_text = str_replace($replace, '', $tweet_text);
      }

      $tweet_build = [
        '#theme' => 'twitter_api__tweet',
        '#user_link' => Link::fromTextAndUrl('@' . $tweet['user']['name'], Url::fromUri('https://twitter.com/' . $tweet['user']['screen_name'])),
        '#text' => new FormattableMarkup($tweet_text, []),
        '#timestamp' => strtotime($tweet['created_at']),
        '#tweet_url' => Url::fromUri('https://twitter.com/' . $tweet['user']['screen_name'] . '/status/' . $tweet['id'])->toString(),
      ];

      // Render the first image in the tweet.
      if (!empty($images)) {
        $tweet_build['#image'] = [
          '#theme' => 'image',
          '#uri' => array_shift($images),
          '#alt' => $this->t('Photo 1'),
        ];
      }

      $tweets[] = $tweet_build;
    }
    $build = [
      '#theme' => 'twitter_api__tweet_list',
      '#tweets' => $tweets,
      '#cache' => [
        'max-age' => $this->configuration['cache'],
      ],
    ];

    return $build;
  }

}
