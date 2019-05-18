<?php

namespace Drupal\adsense_oldcode\Plugin\Block;

use Drupal\adsense\AdBlockInterface;
use Drupal\adsense_oldcode\Plugin\AdsenseAd\OldSearchAd;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an AdSense Custom Search ad block.
 *
 * @Block(
 *   id = "adsense_oldsearch_ad_block",
 *   admin_label = @Translation("Old search"),
 *   category = @Translation("Adsense")
 * )
 */
class OldSearchAdBlock extends BlockBase implements AdBlockInterface, ContainerFactoryPluginInterface {

  /**
   * Configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Creates a new AdsenseAdBase instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The configuration.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ImmutableConfig $config) {
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
      $container->get('config.factory')->get('adsense_search.settings')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'ad_channel' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function createAd() {
    return new OldSearchAd(['channel' => $this->configuration['ad_channel']]);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->createAd()->display();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Hide block title by default.
    $form['label_display']['#default_value'] = FALSE;

    $channel_list = [];
    for ($channel = 1; $channel <= ADSENSE_OLDCODE_MAX_CHANNELS; $channel++) {
      $title = $this->config->get('adsense_ad_channel_' . $channel);
      if (!empty($title)) {
        $channel_list[$channel] = $title;
      }
    }

    $form['ad_channel'] = [
      '#type' => 'select',
      '#title' => $this->t('Channel'),
      '#default_value' => $this->configuration['ad_channel'],
      '#options' => $channel_list,
      '#empty_value' => '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['ad_channel'] = $form_state->getValue('ad_channel');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    /*return Cache::PERMANENT;*/
    return 0;
  }

}
