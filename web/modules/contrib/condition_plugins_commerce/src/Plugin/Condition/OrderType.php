<?php

namespace Drupal\condition_plugins_commerce\Plugin\Condition;

use Drupal\condition_plugins_commerce\Plugin\ConditionBase;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an order type condition.
 *
 * @Condition(
 *   id = "condition_plugins_commerce_order_type",
 *   label = @Translation("Commerce order bundle"),
 *   context = {
 *     "commerce_order" = @ContextDefinition("entity:commerce_order", label = @Translation("Order"))
 *   }
 * )
 */
class OrderType extends ConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Creates a new OrderType instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['bundles' => []] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    $commerce_order_types = $this->entityTypeManager->getStorage('commerce_order_type')->loadMultiple();
    foreach ($commerce_order_types as $type) {
      $options[$type->id()] = $type->label();
    }
    $form['bundles'] = [
      '#title' => $this->t('Order types'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $this->configuration['bundles'],
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['bundles'] = array_filter($form_state->getValue('bundles'));

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (count($this->configuration['bundles']) > 1) {
      $bundles = $this->configuration['bundles'];
      $last = array_pop($bundles);
      $bundles = implode(', ', $bundles);
      return $this->t('The order bundle @comparison @bundles or @last',
        [
          '@comparison' => (empty($this->configuration['negate'])) ? $this->t('is') : $this->t('is not'),
          '@bundles' => $bundles,
          '@last' => $last,
        ]
      );
    }
    $bundle = reset($this->configuration['bundles']);

    return $this->t('The order bundle @comparison @bundle',
      [
        '@comparison' => (empty($this->configuration['negate'])) ? $this->t('is') : $this->t('is not'),
        '@bundle' => $bundle,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['bundles']) && !$this->isNegated()) {
      return TRUE;
    }

    /** @var \Drupal\commerce_order\Entity\OrderInterface $commerce_order */
    $commerce_order = $this->getContextValue('commerce_order');

    return !empty($this->configuration['bundles'][$commerce_order->bundle()]);
  }

}
