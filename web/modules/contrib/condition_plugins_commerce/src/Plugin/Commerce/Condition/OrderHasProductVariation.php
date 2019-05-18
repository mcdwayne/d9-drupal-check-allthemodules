<?php

namespace Drupal\condition_plugins_commerce\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the order has product variation condition.
 *
 * @CommerceCondition(
 *   id = "condition_plugins_commerce_order_has_product_variation",
 *   label = @Translation("Order has product variation"),
 *   display_label = @Translation("Limit to orders having certain product variations"),
 *   category = @Translation("Order"),
 *   entity_type = "commerce_order",
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
    $this->configuration['product_variations'] = array_column($values['product_variations'], 'target_id');
    // Update form values.
    $form_state->setvalue(array_merge($form['#parents'], ['product_variations']), $this->configuration['product_variations']);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $entity */
    $this->assertEntity($entity);

    foreach ($entity->getItems() as $item) {
      if (in_array($item->getPurchasedEntityId(), $this->configuration['product_variations'])) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
