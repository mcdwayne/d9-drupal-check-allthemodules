<?php

namespace Drupal\outlayer\Plugin\views\style;

use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\blazy\Dejavu\BlazyStyleBaseTrait;
use Drupal\blazy\Dejavu\BlazyStyleOptionsTrait;
use Drupal\blazy\BlazyManagerInterface;
use Drupal\outlayer\OutlayerManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base Outlayer style plugin.
 */
class OutlayerViewsBase extends StylePluginBase {

  use BlazyStyleBaseTrait;
  use BlazyStyleOptionsTrait;

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * The outlayer service manager.
   *
   * @var \Drupal\outlayer\OutlayerManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlazyManagerInterface $blazy_manager, OutlayerManagerInterface $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blazyManager = $blazy_manager;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('blazy.manager'), $container->get('outlayer.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function admin() {
    return \Drupal::service('outlayer.admin');
  }

  /**
   * {@inheritdoc}
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * Returns item list suitable for button groups.
   */
  public function buildItemList(array $items, array $settings, $type = 'sorter') {
    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#context' => ['settings' => $settings],
      '#attributes' => [
        'class' => [
          'outlayer-list',
          'outlayer-list--' . $type,
          'btn-group',
        ],
        'data-instance-id' => $settings['instance_id'],
      ],
      '#wrapper_attributes' => [
        'class' => ['item-list--outlayer', 'item-list--outlayer-' . $type],
      ],
    ];
  }

}
