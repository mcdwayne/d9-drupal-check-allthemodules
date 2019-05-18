<?php

/**
 * @file
 * Contains Drupal\google_plus_feeds\Plugin\Block\GooglePlusFeedBlock.
 */

namespace Drupal\google_plus_feeds\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Block\BlockManagerInterface;
use GuzzleHttp\Exception\RequestException;
use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Provides a "Google Plus Feeds" block.
 *
 * @Block(
 *   id = "google_plus_feeds",
 *   admin_label = @Translation("Google+ posts"),
 *   category = @Translation("Google Plus Feeds")
 * )
 */

class GooglePlusFeedBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The config_factory variable.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an GooglePlusFeedBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The Plugin Block Manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface
   * $string_translation
   *   The string translation service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $configFactory, BlockManagerInterface $block_manager, TranslationInterface $string_translation) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configFactory = $configFactory;
    $this->blockManager  = $block_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('plugin.manager.block'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Get Configuration.
    $settings = $this->configFactory->getEditable('google_plus_feeds.adminsettings');
    $account_id = $settings->get('google_plus_account_id');
    $google_api_key = $settings->get('google_plus_account_api_key');

    $feed_data = $this->getGooglePlusFeedsData($account_id, $google_api_key);

    $output = [
      '#theme' => 'google_plus_feeds_post',
      '#google_post' => $feed_data,
      '#attached' => [
        'library' => ['google_plus_feeds/google_plus_feeds.block'],
      ]
    ];

    return $output;
  }

/**
 * Fetch G+ posts from the user account
 * @param  varchar $account_id
 *    Account ID configure in admin page.
 * @param  string $account_key
 * @return array
 */
  public function getGooglePlusFeedsData($account_id, $account_key) {
    $client = \Drupal::httpClient();
    $response = $client->request('GET', 'https://www.googleapis.com/plus/v1/people/' . $account_id . '/activities/public?maxResults=3&key=' . $account_key, ['verify' => false]);
    $content = (string)$response->getBody();
    $data = Json::decode($content);
    $google_plus_posts_list = array();
    if (array_key_exists('items', $data)) {
      foreach ($data['items'] as $key => $value) {
        $title = $value['title'] ? $value['title'] : $value['url'];
        $google_plus_posts_list[] = [
          'title' => text_summary($title, NULL, GOOGLE_PLUS_FEEDS_MAX_LENGTH),
          'url' => $value['url'],
        ];
      }
    }

    $g_plus_url = 'https://plus.google.com/' . $account_id . '/posts';

    $google_plus_posts_list += [
      'more_title' => $this->t('See more posts...'),
      'more_url' => $g_plus_url,
    ];

    return $google_plus_posts_list;
  }
}
