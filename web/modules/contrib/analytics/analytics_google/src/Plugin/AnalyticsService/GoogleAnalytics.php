<?php

namespace Drupal\analytics_google\Plugin\AnalyticsService;

use Drupal\analytics\AnalyticsService\AnalyticsServiceBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Analytics service type.
 *
 * @AnalyticsService(
 *   id = "google_analytics",
 *   label = @Translation("Google Analytics"),
 *   description = @Translation("Google Analytics tracking.")
 * )
 */
class GoogleAnalytics extends AnalyticsServiceBase implements ContainerFactoryPluginInterface {
  use PluginDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  public function getDefaultOptions() {
    return [
      'id' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(&$form, &$form_state) {
    $form['id'] = [
      '#type' => 'number',
      '#title' => t('Tracking ID'),
      '#default_value' => $this->getConfigurationValue('id'),
      '#min' => 0,
      '#required' => TRUE,
      '#size' => 15,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutput() {
    $config_instances = \Drupal::service('entity_type.manager')->getStorage('analytics_instance')->loadMultiple();
    $plugin_id = $this->getPluginId();
    $output = [];
    // This is just placeholder code.
    foreach ($config_instances as $config_instance) {
      if ($plugin_id == $config_instance->type_id) {
        $output['analytics_' . $config_instance->id] = [
          '#type' => 'html_tag',
          '#tag' => 'googleanalytics',
          '#attributes' => [
            'tracking_id' => $config_instance->type_settings['id'],
          ],
        ];
      }
    }
    return $output;
  }
}
