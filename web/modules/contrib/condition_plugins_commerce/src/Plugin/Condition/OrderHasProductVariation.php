<?php

namespace Drupal\condition_plugins_commerce\Plugin\Condition;

use Drupal\condition_plugins_commerce\Plugin\ConditionBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the order has product variation condition.
 *
 * @Condition(
 *   id = "condition_plugins_commerce_order_has_product_variation",
 *   label = @Translation("Order has product variation"),
 *   context = {
 *     "commerce_order" = @ContextDefinition("entity:commerce_order", label = @Translation("Order"))
 *   }
 * )
 */
class OrderHasProductVariation extends ConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new OrderHasProductVariation instance.
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
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
    return [
      'product_variations' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['product_variations'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Required product variations'),
      '#target_type' => 'commerce_product_variation',
      '#tags' => TRUE,
      '#default_value' => empty($this->configuration['product_variations']) ? NULL : $this->entityTypeManager->getStorage('commerce_product_variation')->loadMultiple($this->configuration['product_variations']),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    if (count($values['product_variations'])) {
      $product_variations = array_column($values['product_variations'], 'target_id');
      $this->configuration['product_variations'] = $product_variations;
      // Update form values.
      $form_state->setvalue(array_merge($form['#parents'], ['product_variations']), $this->configuration['product_variations']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (count($this->configuration['product_variations']) > 1) {
      $variations = $this->configuration['product_variations'];
      $last = array_pop($variations);
      $variations = implode(', ', $variations);
      return $this->t('@comparison production variation @variations or @last.',
        [
          '@comparison' => (empty($this->configuration['negate'])) ? $this->t('Has') : $this->t('Has not'),
          '@variations' => $variations,
          '@last' => $last,
        ]
      );
    }
    $variations = reset($this->configuration['product_variations']);

    return $this->t('@comparison production variation @variations.',
      [
        '@comparison' => (empty($this->configuration['negate'])) ? $this->t('Has') : $this->t('Has not'),
        '@variations' => $variations,
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $commerce_order */
    $commerce_order = $this->getContextValue('commerce_order');

    foreach ($commerce_order->getItems() as $item) {
      if (in_array($item->getPurchasedEntityId(), $this->configuration['product_variations'])) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
