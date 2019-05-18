<?php

namespace Drupal\analytics_amp\Plugin\AnalyticsService;

use Drupal\analytics\AnalyticsService\AnalyticsServiceBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Html;

/**
 * Analytics service type.
 *
 * @AnalyticsService(
 *   id = "amp_tracking_pixel",
 *   label = @Translation("AMP Tracking Pixel"),
 *   description = @Translation("An AMP Tracking Pixel plugin.")
 * )
 */
class AmpTrackingPixel extends AnalyticsServiceBase implements ContainerFactoryPluginInterface {
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
      'url' => NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(&$form, &$form_state) {
    $form['url'] = [
      '#title' => t('URL'),
      '#type' => 'textfield',
      '#default_value' => $this->getConfigurationValue('url'),
      '#description' => $this->t('See the <a href="@url">substitutions guide</a> to see what variables can be included in the URL.', ['@url' => 'https://github.com/ampproject/amphtml/blob/master/spec/amp-var-substitutions.md']),
      '#required' => TRUE,
      '#placeholder' => 'https://foo.com/pixel?RANDOM',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getOutput() {
    if (\Drupal::service('router.amp_context')->isAmpRoute()) {
      $plugin_id = $this->getPluginId();
      $config_instances = \Drupal::service('entity_type.manager')->getStorage('analytics_instance')->loadMultiple();
      $output = [];
      foreach ($config_instances as $config_instance) {
        if ($plugin_id == $config_instance->type_id) {
          $output['analytics_' . $config_instance->id] = [
            '#type' => 'html_tag',
            '#tag' => 'amp-pixel',
            '#attributes' => [
              'src' => $config_instance->type_settings['url'],
            ],
          ];
        }
      }
      return $output;
    }
    else {
      return [];
    }
  }
}
