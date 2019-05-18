<?php

namespace Drupal\condition_plugins_commerce\Plugin\Commerce\Condition;

use Drupal\condition_plugins_commerce\Plugin\ConditionBase;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the order has a product variation with field value condition.
 *
 * @CommerceCondition(
 *   id = "condition_plugins_commerce_order_has_product_variation_with_base_field_value",
 *   label = @Translation("Order has product variation with base field value"),
 *   context = {
 *     "commerce_order" = @ContextDefinition("entity:commerce_order", label = @Translation("Order"))
 *   }
 * )
 */
class OrderHasProductVariationWithBaseFieldValue extends ConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Creates a new OrderHasProductVariationWithBaseFieldValue instance.
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
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManager $entity_field_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'base_field' => NULL,
      'operator' => NULL,
      'base_field_value' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $options = [];
    foreach ($this->entityFieldManager->getBaseFieldDefinitions('commerce_product_variation') as $field_name => $field_definition) {
      $options[$field_name] = $field_definition->getLabel();
    }

    $form['base_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#options' => $options,
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => empty($this->configuration['base_field']) ? NULL : $this->configuration['base_field'],
    ];
    $form['operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Comparison operator'),
      '#options' => $this->getComparisonOperators(),
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => empty($this->configuration['operator']) ? NULL : $this->configuration['operator'],
    ];
    $form['base_field_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#default_value' => empty($this->configuration['base_field_value']) ? NULL : $this->configuration['base_field_value'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['base_field'] = $values['base_field'];
    $this->configuration['operator'] = $values['operator'];
    $this->configuration['base_field_value'] = $values['base_field_value'];
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $operators = $this->getComparisonOperators();

    $t_args = [
      '@comparison' => !empty($this->configuration['status']) ? $this->t('Do not return') : $this->t('Return'),
      '@field' => $this->configuration['base_field'],
      '@operator' => mb_strtolower($operators[$this->configuration['operator']]),
      '@value' => empty($this->configuration['base_field_value']) ? $this->t('empty') : $this->configuration['base_field_value'],
    ];

    return $this->t('@comparison true if @field is @operator @value.', $t_args);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $commerce_order */
    $commerce_order = $this->getContextValue('commerce_order');


    foreach ($commerce_order->getItems() as $item) {
      if ($item->getPurchasedEntity()->hasField($this->configuration['base_field']) &&
        array_key_exists('value', $item->getPurchasedEntity()->get($this->configuration['base_field'])->first()->getValue())) {
        $value = $item->getPurchasedEntity()->get($this->configuration['base_field'])->first()->getValue()['value'];

        switch ($this->configuration['operator']) {
          case '<':
            return $value < $this->configuration['base_field_value'];

          case '<=':
            return $value <= $this->configuration['base_field_value'];

          case '>=':
            return $value >= $this->configuration['base_field_value'];

          case '>':
            return $value > $this->configuration['base_field_value'];

          case '==':
            return $value == $this->configuration['base_field_value'];
        }
      }
    }

    return FALSE;
  }

}
