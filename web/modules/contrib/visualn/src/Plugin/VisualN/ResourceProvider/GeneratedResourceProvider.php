<?php

// @todo: rename class to GeneratedDataResourceProvider

namespace Drupal\visualn\Plugin\VisualN\ResourceProvider;

use Drupal\visualn\Core\ResourceProviderBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\NestedArray;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\visualn\Manager\DataGeneratorManager;
use Drupal\visualn\Helpers\VisualNFormsHelper;
use Drupal\Core\Url;
use Drupal\visualn\Helpers\VisualN;

/**
 * Provides a 'VisualN Generated Resource Provider' VisualN resource provider.
 *
 * @VisualNResourceProvider(
 *  id = "visualn_generated_data",
 *  label = @Translation("Generated data"),
 * )
 */
class GeneratedResourceProvider extends ResourceProviderBase implements ContainerFactoryPluginInterface {

  const RAW_RESOURCE_FORMAT = 'visualn_generic_data_array';

  /**
   * Drupal\visualn\Manager\DataGeneratorManager definition.
   *
   * @var \Drupal\visualn\Manager\DataGeneratorManager
   */
  protected $visualNDataGeneratorManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.visualn.data_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DataGeneratorManager $plugin_manager_visualn_data_generator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->visualNDataGeneratorManager = $plugin_manager_visualn_data_generator;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'data_generator_id' => '',
      'data_generator_config' => [],
    ] + parent::defaultConfiguration();
  }

  public function getResource() {
    if ($this->configuration['data_generator_id']) {
      $data_generator_id = $this->configuration['data_generator_id'];
      $data_generator_config = $this->configuration['data_generator_config'];
      $generator_plugin = $this->visualNDataGeneratorManager->createInstance($data_generator_id, $data_generator_config);
      $resource = $generator_plugin->generateResource();
    }
    else {
      // @todo: or return NULL or FASLE if data generator not defined
      //   or an "empty" resource
      $data = [];
      $raw_resource_plugin_id = static::RAW_RESOURCE_FORMAT;
      $raw_input = [
        'data' => $data,
      ];
      $resource = \Drupal::service('plugin.manager.visualn.raw_resource_format')
        ->createInstance($raw_resource_plugin_id, [])
        ->buildResource($raw_input);
    }


    return $resource;
  }



  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $definitions = $this->visualNDataGeneratorManager->getDefinitions();
    $data_generators = [];
    foreach ($definitions as $definition) {
      $data_generators[$definition['id']] = $definition['label'];
    }

    $ajax_wrapper_id = implode('-', array_merge($form['#array_parents'], ['data_generator_id'])) .'-ajax-wrapper';

    $form['data_generator_id'] = [
      '#type' => 'select',
      '#title' => t('Data Generator'),
      '#options' => $data_generators,
      '#default_value' => $this->configuration['data_generator_id'],
      '#required' => TRUE,
      '#empty_option' => t('- Select Data Generator -'),
      '#empty_value' => '',
      '#ajax' => [
        'callback' => [get_called_class(), 'ajaxCallbackDataGenerator'],
        'wrapper' => $ajax_wrapper_id,
      ],
    ];
    $form['generator_container'] = [
      '#prefix' => '<div id="' . $ajax_wrapper_id . '">',
      '#suffix' => '</div>',
      '#type' => 'container',
      '#process' => [[$this, 'processGeneratorContainerSubform']],
    ];
    $form['generator_container']['#stored_configuration'] = [
      'data_generator_id' => $this->configuration['data_generator_id'],
      'data_generator_config' => $this->configuration['data_generator_config'],
    ];

    return $form;
  }

  /**
   * Return data generator configuration form via ajax request at style change.
   * Should have a different name since ajaxCallback can be used by base class.
   */
  public static function ajaxCallbackDataGenerator(array $form, FormStateInterface $form_state, Request $request) {
    $triggering_element = $form_state->getTriggeringElement();
    $visualn_style_id = $form_state->getValue($form_state->getTriggeringElement()['#parents']);
    $triggering_element_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $element = NestedArray::getValue($form, $triggering_element_parents);

    return $element['generator_container'];
  }

  // @todo: this should be static since may not work on field settings form (see fetcher field widget for example)
  //public static function processGeneratorContainerSubform(array $element, FormStateInterface $form_state, $form) {
  public function processGeneratorContainerSubform(array $element, FormStateInterface $form_state, $form) {
    $stored_configuration = $element['#stored_configuration'];
    $configuration = [
      'data_generator_id' => $stored_configuration['data_generator_id'],
      'data_generator_config' => $stored_configuration['data_generator_config'],
    ];
    $element = VisualNFormsHelper::doProcessGeneratorContainerSubform($element, $form_state, $form, $configuration);
    return $element;
  }

}

