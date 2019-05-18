<?php

namespace Drupal\condition_plugins_commerce\Plugin\Commerce\Condition;

use Drupal\commerce\Plugin\Commerce\Condition\ConditionBase;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the order has field value condition.
 *
 * @CommerceCondition(
 *   id = "condition_plugins_commerce_order_has_base_field_value",
 *   label = @Translation("Order has base field value"),
 *   display_label = @Translation("Limit to orders having certain base field value"),
 *   category = @Translation("Order"),
 *   entity_type = "commerce_order",
 * )
 */
class OrderHasBaseFieldValue extends ConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Creates a new OrderHasBaseFieldValue instance.
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
    foreach ($this->entityFieldManager->getBaseFieldDefinitions('commerce_order') as $field_name => $field_definition) {
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
  public function evaluate(EntityInterface $entity) {
    /** @var \Drupal\commerce_order\Entity\OrderInterface $entity */
    $this->assertEntity($entity);

    if ($entity->hasField($this->configuration['base_field']) &&
      array_key_exists('value', $entity->get($this->configuration['base_field'])->first()->getValue())) {
      $value = $entity->get($this->configuration['base_field'])->first()->getValue()['value'];

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

    return FALSE;
  }

}
