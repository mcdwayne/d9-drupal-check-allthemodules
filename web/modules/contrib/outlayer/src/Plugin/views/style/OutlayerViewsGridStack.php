<?php

namespace Drupal\outlayer\Plugin\views\style;

use Drupal\blazy\BlazyManagerInterface;
use Drupal\gridstack\Plugin\views\style\GridStackViews;
use Drupal\outlayer\OutlayerManagerInterface;
use Drupal\outlayer\OutlayerDefault;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Outlayer base style plugin for Isotope and Grid.
 */
class OutlayerViewsGridStack extends GridStackViews {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlazyManagerInterface $blazy_manager, OutlayerManagerInterface $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $blazy_manager, $manager);
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
  protected function defineOptions() {
    $options = [];
    $settings = OutlayerDefault::gridSettings() + OutlayerDefault::viewsSettings();
    foreach ($settings as $key => $value) {
      $options[$key] = ['default' => $value];
    }
    return $options + parent::defineOptions();
  }

}
