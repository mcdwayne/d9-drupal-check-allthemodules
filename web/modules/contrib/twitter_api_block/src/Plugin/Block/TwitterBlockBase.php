<?php

namespace Drupal\twitter_api_block\Plugin\Block;

use Drupal\block\Entity\Block;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use TwitterAPIExchange;

/**
 * Provides a 'TwitterBlock' block.
 *
 * @see https://github.com/J7mbo/twitter-api-php
 */
class TwitterBlockBase extends BlockBase implements ContainerFactoryPluginInterface {

  use UncacheableDependencyTrait;

  /**
   * Prefix used to saved values with State API.
   */
  const TWITTER_APPNAME_PREFIX = 'twitter_api_block.';

  /**
   * Allowed size of application name in Twitter Developer platform.
   */
  const TWITTER_APPNAME_LENGTH = 29;

  /**
   * The API configuration to talk to Twitter.
   *
   * @var array
   */
  protected $credentials;

  /**
   * The state key/value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The Twitter API Object.
   *
   * @var TwitterAPIExchange
   */
  protected $twitter;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Request $request, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    // Get the state service.
    $this->state = $state;

    // Populate API credentials.
    $application = self::TWITTER_APPNAME_PREFIX . $this->getConfiguration()['application'];
    $credentials = $this->state->get($application);

    $this->credentials = [
      'consumer_key'              => isset($credentials['consumer_key']) ? $credentials['consumer_key'] : NULL,
      'consumer_secret'           => isset($credentials['consumer_secret']) ? $credentials['consumer_secret'] : NULL,
      'oauth_access_token'        => isset($credentials['oauth_access_token']) ? $credentials['oauth_access_token'] : NULL,
      'oauth_access_token_secret' => isset($credentials['oauth_access_token_secret']) ? $credentials['oauth_access_token_secret'] : NULL,
    ];

    // Instanciate our Twitter API object.
    if ($this->hasCredentials()) {
      $this->twitter = new TwitterAPIExchange($this->credentials);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'application' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $credentials = $this->credentials;
    $config      = $this->getConfiguration();
    $block       = $form_state->getFormObject()->getEntity();

    // Name of the credentials configuration.
    $form['application'] = [
      '#type'          => 'machine_name',
      '#title'         => $this->t('Twitter application name'),
      '#description'   => $this->t('A unique name for this item. It must only contain lowercase letters, numbers, and underscores.'),
      '#default_value' => $config['application'],
      '#maxlength'     => self::TWITTER_APPNAME_LENGTH,
      '#required'      => TRUE,
      '#machine_name'  => [
        'exists' => [$this, 'machineNameExists'],
      ],
      '#disabled'      => !$block->isNew() && !empty($credentials['consumer_secret']),
    ];

    // Twitter credentials.
    $form['api'] = [
      '#type'        => 'details',
      '#title'       => $this->t('Twitter credentials'),
      '#description' => $this->t('Create a new application at <a href="https://apps.twitter.com/" target="_blank">https://apps.twitter.com/</a>.'),
      '#collapsible' => TRUE,
      '#open'        => TRUE,
      '#access'      => $block->isNew() || empty($credentials['consumer_secret']),
    ];
    $form['api']['consumer_key'] = [
      '#title'         => t('Consumer Key'),
      '#type'          => 'textfield',
      '#default_value' => $credentials['consumer_key'],
      '#required'      => TRUE,
    ];
    $form['api']['consumer_secret'] = [
      '#title'         => t('Consumer Secret'),
      '#type'          => 'textfield',
      '#default_value' => $credentials['consumer_secret'],
      '#required'      => TRUE,
    ];
    $form['api']['oauth_access_token'] = [
      '#title'         => t('OAuth Access Token'),
      '#type'          => 'textfield',
      '#default_value' => $credentials['oauth_access_token'],
      '#required'      => TRUE,
    ];
    $form['api']['oauth_access_token_secret'] = [
      '#title'         => t('OAuth Access Token Secret'),
      '#type'          => 'textfield',
      '#default_value' => $credentials['oauth_access_token_secret'],
      '#required'      => TRUE,
    ];

    // Basic block options.
    $form['options'] = [
      '#type'        => 'details',
      '#title'       => $this->t('Options'),
      '#collapsible' => false,
      '#open'        => TRUE,
    ];
    $form['options']['count'] = [
      '#type'          => 'number',
      '#title'         => $this->t("Number of tweets"),
      '#default_value' => isset($config['options']['count']) ? $config['options']['count'] : 3,
      '#required'      => TRUE,
    ];

    // Cache-related.
    $form['options']['disable_cache'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t("Disable cache"),
      '#description'   => $this->t("" .
        "If checked, this block will trigger the `page_cache_kill_switch` when displayed." .
        "This has impact on your site's performance." .
        "Use with caution."),
      '#default_value' => isset($config['options']['disable_cache']) ? $config['options']['disable_cache'] : 0,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values      = $form_state->getValues();
    $credentials = $values['api'];
    unset($values['api']);

    // Save all the rest of custom settings in block's configuration.
    foreach ($values as $key => $value) {
      $this->setConfigurationValue($key, $value);
    };

    // Save credentials securely.
    $application = $this->getConfiguration()['application'];
    $this->state->set(self::TWITTER_APPNAME_PREFIX . $application, $credentials);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build  = [];
    $config = $this->getConfiguration();

    if (!$this->hasCredentials()) {
      return [];
    }

    // Attach JS and styles.
    $build['#attached']['library'][] = 'twitter_api_block/base';

    // Cache depending on the current user.
    $build['#cache'] = [
      'contexts' => [
        'user',
      ],
    ];

    if (isset($this->configuration['disable_cache']) && $this->configuration['disable_cache']) {
      // Do not cache any page with this block on it.
      \Drupal::service('page_cache_kill_switch')->trigger();

      // Disable caching.
      $build['#cache'] = [
        'contexts' => [
          'user',
        ],
        'max-age'  => 0,
      ];
    }

    return $build;
  }

  /**
   * Required function for machine name validation.
   *
   * @todo Double check other Twitter Blocks don't have save the same application name.
   */
  public function machineNameExists($value, $element, $form_state) {
    // Load existing applications in State using our collection name.
    return FALSE;
  }

  /**
   * Helpers function to prevent rendering an empty block.
   *
   * @return boolean
   */
  public function hasCredentials() {
    $credentials = $this->credentials;

    // Stop now if credentials are not setup.
    if (
      NULL == $credentials['consumer_key'] ||
      NULL == $credentials['consumer_secret'] ||
      NULL == $credentials['oauth_access_token'] ||
      NULL == $credentials['oauth_access_token_secret']
    ) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Pull Tweets from Twitter API.
   *
   * @return array
   */
  public function getTweets(string $url, string $parameters) {
    $tweets = [];

    try {
      $result = $this->twitter->setGetfield($parameters)->buildOauth($url, 'GET')->performRequest();
      $tweets = Json::decode($result);
    } catch (\Exception $e) {
      \Drupal::logger('twitter')->error($e->getMessage());
    }

    return $tweets;
  }

  /**
   * Render Tweets.
   */
  public function renderTweets(array $tweets) {
    $embed  = [];
    $config = $this->getConfiguration();

    $i = 0;
    foreach ($tweets as $tweet) {
      if (isset($tweet['id']) && $i < $config['options']['count']) {
        $url     = 'https://publish.twitter.com/oembed?url=https%3A%2F%2Ftwitter.com%2F' . $tweet['user']['screen_name'] . '%2Fstatus%2F' . $tweet['id'];
        $data    = $this->twitter->buildOauth($url, 'GET')->performRequest();
        $embed[] = Json::decode($data);
      }
      $i++;
    }
    return $embed;
  }

  /**
   * Render Tweets.
   */
  public function displayTweets(array $embed) {
    $build = [];
    foreach ($embed as $tweet) {
      $build[] = ['#markup' => $tweet['html']];
    }
    return $build;
  }

}
