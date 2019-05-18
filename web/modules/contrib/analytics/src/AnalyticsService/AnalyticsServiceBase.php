<?php

namespace Drupal\analytics\AnalyticsService;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Plugin\PluginBase;

/**
 * Defines a base implementation that most analytics service plugins will extend.
 *
 * @ingroup analytics_api
 */
class AnalyticsServiceBase extends PluginBase implements AnalyticsServiceInterface {
  use StringTranslationTrait;

  private $machine_name;
  private $label;
  private $description;
  private $service;
  private $options;
  protected $hasMultiple;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->options = NestedArray::mergeDeep($this->defaultConfiguration(), $this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName() {
    return $this->machine_name;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getService() {
    return $this->service;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationValue($name, $default = NULL) {
    $configuration = $this->getConfiguration();
    if (array_key_exists($name, $configuration)) {
      return $configuration[$name];
    }
    else {
      return $default;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface &$form_state) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function canTrack() {
    $route = \Drupal::routeMatch()->getRouteObject();
    return !\Drupal::service('router.admin_context')->isAdminRoute($route);
  }

  public function hasMultipleInstances() {
    if (!isset($this->hasMultiple)) {
      $services =  \Drupal::service('entity_type.manager')->getStorage('analytics_instance')->loadMultiple();
      $count = 0;
      foreach ($services as $service) {
        if ($service->service == $this->getService()) {
          $count++;
        }
      }
      $this->hasMultiple = $count >= 2;
    }
    return $this->hasMultiple;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableUrls() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getOutput() {
    return [];
  }

  /**
   * Build an <amp-analytics> tag for output on an amp-enabled page request.
   *
   * @param array $service_id
   *   The ID of the analytics service.
   *
   * @return array
   *   A structured, renderable array.
   */
  public function getAmpOutput($service_id) {
    $config_instances = \Drupal::service('entity_type.manager')->getStorage('analytics_instance')->loadMultiple();
    $output = [];
    foreach ($config_instances as $config_instance) {
      if ($service_id == $config_instance->type_id) {
        $element = [
          '#type' => 'html_tag',
          '#tag' => 'amp-analytics',
          '#attached' => [
            'library' => 'amp/amp.analytics'
          ]
        ];
        if (!empty($json = $config_instance->type_settings['config_json'])) {
          $json_element = [
            '#type' => 'html_tag',
            '#tag' => 'script',
            '#attributes' => [
              'type' => 'application/ld+json'
            ],
            '#value' => $json,
          ];
          $element['#value'] = \Drupal::service('renderer')->renderPlain($json_element);
        }
        if (!empty($type = $config_instance->type_settings['type'])) {
          $element['#attributes']['type'] = $type;
        }
        if (!empty($config_url = $config_instance->type_settings['config_url'])) {
          $element['#attributes']['config'] = $config_url;
        }
        $output['analytics_' . $config_instance->id] = $element;
      }
    }

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->hasAnyErrors()) {
      $this->setConfiguration(
        array_intersect_key(
          $form_state->getValues(),
          $this->defaultConfiguration()
        )
      );
    }
  }

}
