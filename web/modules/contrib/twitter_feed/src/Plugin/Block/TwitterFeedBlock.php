<?php

namespace Drupal\twitter_feed\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;

/**
 * Provides a 'TwitterFeedBlock' block.
 *
 * @Block(
 *  id = "twitter_feed_block",
 *  admin_label = @Translation("Twitter feed block"),
 * )
 */
class TwitterFeedBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * GuzzleHttp\Client definition.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        Client $http_client
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $default = [
      'number_of_tweets' => 3,
      'username' => 'drupal',
      'display_images' => FALSE,
      'display_avatars' => FALSE,
    ];
    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['number_of_tweets'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of tweets'),
      '#description' => $this->t('Fetch and display only this number of feeds'),
      '#default_value' => $this->configuration['number_of_tweets'],
    ];
    $form['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username to display'),
      '#description' => $this->t('User to fetch and display tweets'),
      '#default_value' => $this->configuration['username'],
    ];
    $form['display_images'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display images'),
      '#description' => $this->t('If images embedded in the tweet should be expanded and embedded'),
      '#default_value' => $this->configuration['display_images'],
    ];
    $form['display_avatars'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display avatars'),
      '#description' => $this->t("If tweeter's avatar should be displayed"),
      '#default_value' => $this->configuration['display_avatars'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['number_of_tweets'] = $form_state->getValue('number_of_tweets');
    $this->configuration['username'] = $form_state->getValue('username');
    $this->configuration['display_images'] = $form_state->getValue('display_images');
    $this->configuration['display_avatars'] = $form_state->getValue('display_avatars');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('twitter_feed.settings');
    // https://dev.twitter.com/oauth/application-only
    $api_key = rawurlencode($config->get('twitter_feed_api_key'));
    $api_secret = rawurlencode($config->get('twitter_feed_api_secret'));
    if (!$api_key || !$api_secret) {
      return ['#markup' => $this->t('API Key or Secret missing for Twitter Feed.')];
    }
    $encoded_key = base64_encode("$api_key:$api_secret");
    $headers = [
      'Authorization' => "Basic $encoded_key",
      'Content-Type' => 'application/x-www-form-urlencoded',
    ];
    $options = [
      'headers' => $headers,
      'timeout' => 10,
      'form_params' => [
        'grant_type' => 'client_credentials',
      ],
      'referer' => TRUE,
      'allow_redirects' => TRUE,
      'decode_content' => 'gzip',
    ];

    try {
      // Get the access token first.
      // https://dev.twitter.com/oauth/reference/post/oauth2/token
      $res = $this->httpClient->post('https://api.twitter.com/oauth2/token', $options);
      $body = json_decode($res->getBody());
      $access_token = $body->access_token;

      // Now get the tweets.
      // https://dev.twitter.com/rest/reference/get/statuses/user_timeline
      $username = $this->configuration['username'];
      $num_tweets = $this->configuration['number_of_tweets'];
      $options['headers']['Authorization'] = "{$body->token_type} $access_token";
      unset($options['headers']['Content-Length']);
      unset($options['form_params']);
      $query = http_build_query([
        'screen_name' => $username,
        'count' => $num_tweets,
      ]);
      // Fetches the tweets.
      $res = $this->httpClient->get("https://api.twitter.com/1.1/statuses/user_timeline.json?$query", $options);
    }
    catch (RequestException $e) {
      return ['#markup' => $this->t('Error fetching tweets:') . $e->getMessage()];
    }

    $renderable_tweets = [];
    foreach (json_decode($res->getBody()) as $tweet_object) {
      $renderable_tweet = [
        '#theme' => 'twitter_feed_item',
        '#tweet' => $tweet_object,
      ];
      $language = \Drupal::config('twitter_feed.settings')->get('twitter_feed_jquery_timeago_locale');
      $renderable_tweet['#attached']['library'][] = 'twitter_feed/timeago';
      if ($language) {
        $renderable_tweet['#attached']['library'][] = 'twitter_feed/timeago_' . $language;
      }
      $renderable_tweets[] = $renderable_tweet;
    }
    if (empty($renderable_tweets)) {
      return ['#markup' => $this->t('Error fetching or rendering tweets.')];
    }
    $item_list = [
      '#items' => $renderable_tweets,
      '#type' => 'ul',
      '#theme' => 'item_list',
      '#attributes' => ['class' => 'twitter-feed'],
    ];
    $build['twitter_feed_list'] = $item_list;
    $build['#cache']['keys'] = ['twitter_feed', $username, "count:$num_tweets"];
    // Cache block for 1 hour by default.
    // TODO set per-block cache time.
    $build['#cache']['max-age'] = 3600;

    return $build;
  }

}
