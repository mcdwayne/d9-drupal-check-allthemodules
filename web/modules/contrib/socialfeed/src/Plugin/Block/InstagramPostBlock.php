<?php

namespace Drupal\socialfeed\Plugin\Block;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\socialfeed\Services\InstagramPostCollectorFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'InstagramPostBlock' block.
 *
 * @Block(
 *  id = "instagram_post_block",
 *  admin_label = @Translation("Instagram Block"),
 * )
 */
class InstagramPostBlock extends SocialBlockBase implements ContainerFactoryPluginInterface, BlockPluginInterface {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Instagram Service.
   *
   * @var \Drupal\socialfeed\Services\InstagramPostCollectorFactory
   */
  protected $instagram;

  /**
   * AccountInterface.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * InstagramPostBlock constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The ConfigFactory $config_factory.
   * @param \Drupal\socialfeed\Services\InstagramPostCollectorFactory $instagram
   *   The InstagramPostCollector $instagram.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The currently logged in user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactory $config_factory, InstagramPostCollectorFactory $instagram, AccountInterface $currentUser) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config_factory->get('socialfeed.instagramsettings');
    $this->instagram = $instagram;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Symfony\Component\DependencyInjection\ContainerInterface parameter.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('socialfeed.instagram'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['overrides']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#description' => $this->t('Client ID from Instagram account'),
      '#default_value' => $this->defaultSettingValue('client_id'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];

    $form['overrides']['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access Token'),
      '#default_value' => $this->defaultSettingValue('access_token'),
      '#size' => 60,
      '#maxlength' => 100,
      '#required' => TRUE,
    ];

    $form['overrides']['picture_count'] = [
      '#type' => 'number',
      '#title' => $this->t('Picture Count'),
      '#default_value' => $this->defaultSettingValue('picture_count'),
      '#size' => 60,
      '#maxlength' => 100,
      '#min' => 1,
    ];

    $form['overrides']['picture_resolution'] = [
      '#type' => 'select',
      '#title' => $this->t('Picture Resolution'),
      '#default_value' => $this->defaultSettingValue('picture_resolution'),
      '#options' => [
        'thumbnail' => $this->t('Thumbnail'),
        'low_resolution' => $this->t('Low Resolution'),
        'standard_resolution' => $this->t('Standard Resolution'),
      ],
    ];

    $this->blockFormElementStates($form);
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   Returning data as an array.
   *
   * @throws \Exception
   */
  public function build() {
    $build = [];
    $items = [];
    $config = $this->config;
    $block_settings = $this->getConfiguration();

    if ($block_settings['override']) {
      $instagram = $this->instagram->createInstance($block_settings['client_id'], $block_settings['access_token']);
    }
    else {
      $instagram = $this->instagram->createInstance($config->get('client_id'), $config->get('access_token'));
    }

    $posts = $instagram->getPosts(
      $this->getSetting('picture_count'),
      $this->getSetting('picture_resolution')
    );

    foreach ($posts as $post) {
      $items[] = [
        '#theme' => 'socialfeed_instagram_post_' . $post['raw']->type,
        '#post' => $post,
        '#cache' => [
          // Cache for 1 hour.
          'max-age' => 60 * 60,
          'cache tags' => $this->config->getCacheTags(),
          'context' => $this->config->getCacheContexts(),
        ],
      ];
    }
    $build['posts'] = [
      '#theme' => 'item_list',
      '#items' => $items,
    ];
    return $build;
  }

}
