<?php

namespace Drupal\search_api_bundle_boost\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SearchApiProcessor(
 *   id = "search_api_bundle_boost_processor",
 *   label = @Translation("Search API Bundle Boost"),
 *   description = @Translation("Enable boosting of specific entity bundles."),
 *   stages = {
 *     "alter_items" = 0
 *   }
 * )
 */
class BundleBoostProcessor extends ProcessorPluginBase implements PluginFormInterface {
	
  use PluginFormTrait;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function __construct(
                              array $configuration,
                              $plugin_id,
                              array $plugin_definition,
                              ConfigFactoryInterface $config_factory,
                              ModuleHandlerInterface $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'bundles' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config_bundles = $this->getConfiguration()['bundles'];

    // Loop through all configured data sources.
    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      $entity_type = $datasource->getEntityTypeId();
      $bundles = $datasource->getBundles();

      // Add entity type fieldset.
      $form['bundles'][$entity_type] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Entity type: %label', ['%label' => $datasource->label()]),
      ];

      // Loop through all configured bundles and add the boost fields
      foreach ($bundles as $bundle => $bundle_label) {
        $form['bundles'][$entity_type][$bundle] = [
          '#type' => 'select',
          '#title' => $bundle_label,
          '#description' => $this->t('Choose the boost value for entity bundle: %bundle.', ['%bundle' => $bundle_label]),
          '#default_value' => isset($config_bundles[$entity_type][$bundle]) ? $config_bundles[$entity_type][$bundle] : [],
          '#options' => $this->getBoostValues(),
        ];
      }
    }

    return $form;
  }

  /**
   * Prepare an array with boost values for the select fields.
   *
   * @return array
   *   Options array with boost values.
   */
  private function getBoostValues() {
    $boost_values = [
      '0.0',
      '0.1',
      '0.2',
      '0.3',
      '0.5',
      '0.8',
      '1.0',
      '2.0',
      '3.0',
      '5.0',
      '8.0',
      '13.0',
      '21.0'
    ];
    return array_combine($boost_values, $boost_values);
  }

  /**
   * {@inheritdoc}
   */
  public function alterIndexedItems(array &$items) {
    $config = $this->getConfiguration()['bundles'];

    // Annoyingly, this doc comment is needed for PHPStorm. See
    // http://youtrack.jetbrains.com/issue/WI-23586
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    foreach ($items as $item_id => $item) {
      $object = $item->getOriginalObject()->getValue();
      $entity_type_id = $object->getEntityTypeId();
      $bundle = $object->bundle();

      // Check if a boost value is set.
      if (!empty($config[$entity_type_id][$bundle])) {
        $boost = $config[$entity_type_id][$bundle];

        // Set the boost value for this entity bundle.
        $items[$item_id]->setBoost($boost);
      }
    }
  }

}
