<?php

namespace Drupal\analytics_piwik\Plugin\AnalyticsService;

use Drupal\analytics\AnalyticsService\AnalyticsServiceBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginDependencyTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Analytics service type.
 *
 * @AnalyticsService(
 *   id = "piwik",
 *   label = @Translation("Piwik"),
 *   description = @Translation("Piwik analyics.")
 * )
 */
class Piwik extends AnalyticsServiceBase implements ContainerFactoryPluginInterface {
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
      'url' => '',
      'id' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(&$form, &$form_state) {
    $form['url'] = [
      '#type' => 'textfield',
      '#title' => t('URL'),
      '#description' => t('The URL to your Piwik base directory.'),
      '#default_value' => $this->getConfigurationValue('url'),
      // @todo Add validation
      //'#element_validate' => array($this->validateUrl),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'number',
      '#title' => t('Site ID'),
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
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getValue('id');
    $url = $form_state->getValue('url');
    $form_state->setValue('id', $id);
    $form_state->setValue('url', $url);

    parent::submitConfigurationForm($form, $form_state);
  }

  function validateUrl($element, &$form_state) {
    $value = $element['#value'];
    if ($value != '') {
      // Make sure the URL is normalized.
      $value = rtrim($value, '/') . '/';
      form_set_value($element, $value, $form_state);

      if (!valid_url($value, TRUE)) {
        form_error($element, t('%name is not a valid URL.', ['%name' => $element['#title']]));
      }
      else {
        $request = drupal_http_request($value . '/piwik.js');
      }
    }
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
          '#tag' => 'piwik',
          '#attributes' => [
            'src' => $config_instance->type_settings['url'],
            'site_id' => $config_instance->type_settings['id'],
          ],
        ];
      }
    }
    return $output;
  }
}
