<?php

namespace Drupal\analytics_amp\Plugin\AnalyticsService;

use Drupal\analytics\AnalyticsService\AnalyticsServiceBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides AMP analytics.
 *
 * @AnalyticsService(
 *   id = "amp_analytics",
 *   label = @Translation("AMP Analytics"),
 *   description = @Translation("An AMP Analytics plugin"),
 * )
 */
class AmpAnalytics extends AnalyticsServiceBase implements ContainerFactoryPluginInterface {
  use PluginDependencyTrait;

  /**
   * {@inheritdoc}
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

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

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'type' => NULL,
      'config_url' => '',
      'config_json' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm($form, &$form_state) {
    $form['type'] = [
      '#title' => t('Type'),
      '#type' => 'select',
      '#default_value' => $this->getConfigurationValue('type'),
      '#options' => [
        // @todo Add support for all options.
        'adobeanalytics' => t('Adobe Analytics'),
        'googleanalytics' => t('Google Analytics'),
      ],
      '#required' => TRUE,
    ];
    $form['config_url'] = [
      '#title' => t('Remote configuration JSON URL'),
      '#type' => 'textfield',
      '#default_value' => $this->getConfigurationValue('config_url'),
      '#placeholder' => 'https://example.com/analytics.config.json',
      // @todo Add URL validation.
      //'#element_validate' => [[$this, 'elementValidateConfigUrl']]
    ];
    $form['config_json'] = [
      '#title' => t('Inline configuration JSON'),
      '#type' => 'textarea',
      '#default_value' => $this->getConfigurationValue('config_json'),
      '#description' => t('See the <a href="https://www.ampproject.org/docs/reference/extended/amp-analytics.html">amp-analytics documentation</a> for example configuration values.'),
      // @todo Add JSON validation.
      #'#element_validate' => [[$this, 'elementValidateConfigJson']]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $type = $form_state->getValue('type');
    $config_url = $form_state->getValue('config_url');
    $config_json = $form_state->getValue('config_json');
    $form_state->setValue('type', $type);
    $form_state->setValue('config_url', $config_url);
    $form_state->setValue('config_json', $config_json);

    parent::submitConfigurationForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function getOutput() {
    if (\Drupal::service('router.amp_context')->isAmpRoute()) {
      return $this->getAmpOutput($this->getPluginId());
    }
    else {
      return [];
    }
  }

  protected function elementValidateConfigUrl($element, &$form_state) {
    $value = $element['#value'];
    if ($value == '') {
      return;
    }
    elseif (file_valid_uri($value)) {
      // Allow file URIs like public:://config.json
      return;
    }
    else {
      elements_validate_url($element, $form_state);
    }
  }

  protected function elementValidateConfigJson($element, &$form_state) {
    $value = $element['#value'];
    if ($value == '') {
      return;
    }
    elseif (is_string($value)) {
      // Otherwise attempt to convert the value to JSON.
      $data = json_decode($value, TRUE);
      if (json_last_error()) {
        form_error($element, t('%name is not valid JSON.', ['%name' => $element['#title']]));
      }
      elseif ($element['#required'] && empty($data)) {
        form_error($element, t('%name is required.', ['%name' => $element['#title']]));
      }
      else {
        // @todo This should attempt to validate the top-level keys.
        form_set_value($element, $data, $form_state);
      }
    }
  }
}
