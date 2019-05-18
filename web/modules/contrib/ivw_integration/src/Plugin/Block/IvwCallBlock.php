<?php

namespace Drupal\ivw_integration\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ivw_integration\IvwTracker;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a IVW call block.
 *
 * @Block(
 *   id = "ivw_integration_call_block",
 *   admin_label = @Translation("IVW call"),
 * )
 */
class IvwCallBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * The main menu object.
   *
   * @var \Drupal\ivw_integration\IvwTracker
   */
  protected $ivwTracker;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an Related Content object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\ivw_integration\IvwTracker $ivw_tracker
   *   The ivw tracker object.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory object.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    IvwTracker $ivw_tracker,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ivwTracker = $ivw_tracker;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
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
      $container->get('ivw_integration.tracker'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $tracker = $this->ivwTracker->getTrackingInformation();
    $config = $this->configFactory->get('ivw_integration.settings');

    // Site is missing, do not render tag.
    if (empty($tracker['st'])) {
      return [];
    }

    $mobile_width = $config->get("mobile_width") ? $config->get("mobile_width") : '';
    $mobile_site = $config->get("mobile_site") ? $config->get("mobile_site") : '';
    $mobile_sv = $tracker['mobile_sv'];

    return [
      'ivw_call' => [
        '#theme' => 'ivw_call',
        '#st' => $tracker['st'],
        '#cp' => $tracker['cp'],
        '#sv' => $tracker['sv'],
        '#sc' => $tracker['sc'],
    // Not yet configurable.
        '#co' => '',
        '#mobile_cp' => $tracker['cpm'],
        '#mobile_st' => $mobile_site,
        '#mobile_sv' => $mobile_sv,
        '#mobile_width' => $mobile_width,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    $parent_tags = parent::getCacheTags();
    return Cache::mergeTags($parent_tags, $this->ivwTracker->getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $parent_context = parent::getCacheContexts();
    return Cache::mergeContexts($parent_context, $this->ivwTracker->getCacheContexts());
  }

}
