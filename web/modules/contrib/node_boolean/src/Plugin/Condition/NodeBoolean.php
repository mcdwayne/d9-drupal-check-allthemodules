<?php

namespace Drupal\node_boolean\Plugin\Condition;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a condition based on a boolean field value.
 *
 * @Condition(
 *   id = "node_boolean",
 *   label = @Translation("Node boolean"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node", required = TRUE , label = @Translation("node"))
 *   }
 * )
 */
class NodeBoolean extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The Entity Field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Creates a new Node Boolean condition plugin instance.
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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];
    foreach ($this->getBooleanNodeFieldMap() as $field_id => $field) {
      $options[$field_id] = ucfirst(str_replace('_', ' ', $field_id));
    }

    $form['boolean'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Fields that should be true (any)'),
      '#default_value' => $this->configuration['boolean'],
      '#options' => $options,
    ];

    $form['all'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Evaluate <b>all</b> fields rather than <b>any</b>'),
      '#default_value' => $this->configuration['all'],
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['boolean'] = array_filter($form_state->getValue('boolean'));
    $this->configuration['all'] = $form_state->getValue('all');
    parent::submitConfigurationForm($form, $form_state);

    if (empty($this->configuration['boolean'])) {
      $this->configuration = [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'boolean' => [],
      'all' => FALSE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['boolean']) && !$this->isNegated()) {
      return TRUE;
    }
    if (isset($this->configuration['all']) && $this->configuration['all']) {
      return $this->evaluateAll();
    }
    else {
      return $this->evaluateAny();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $booleans = $this->configuration['boolean'];
    return $this->t('One of the following fields must evaluate to true: @fields.', ['@fields' => explode(', ', $booleans)]);
  }

  /**
   * Evaluates condition and returns TRUE or FALSE accordingly for any fields.
   *
   * @return bool
   *   TRUE if the condition has been met, FALSE otherwise.
   */
  protected function evaluateAny() {
    $fields = $this->getBooleanNodeFieldMap();
    $node = $this->getContextValue('node');
    foreach ($this->configuration['boolean'] as $field_id) {
      if (isset($fields[$field_id]['bundles'][$node->getType()])) {
        $field_value = FALSE;
        if ($node->get($field_id)->count()) {
          $field_value = $node->get($field_id)->first()->get('value')->getValue();
        }
        if ($field_value) {
          return TRUE;
        }
        elseif ($this->isNegated()) {
          return FALSE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Evaluates condition and returns TRUE or FALSE accordingly for ALL fields.
   *
   * @return bool
   *   TRUE if the condition has been met, FALSE otherwise.
   */
  protected function evaluateAll() {
    $fields = $this->getBooleanNodeFieldMap();
    $node = $this->getContextValue('node');
    foreach ($this->configuration['boolean'] as $field_id) {
      if (isset($fields[$field_id]['bundles'][$node->getType()])) {
        $field_value = FALSE;
        if ($node->get($field_id)->count()) {
          $field_value = $node->get($field_id)->first()->get('value')->getValue();
        }
        if ((!$field_value && !$this->isNegated()) || ($field_value && $this->isNegated())) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  /**
   * Provides a lightweight map of node fields of the boolean type.
   */
  protected function getBooleanNodeFieldMap() {
    $fields = $this->entityFieldManager->getFieldMapByFieldType('boolean');
    return $fields['node'];
  }

}
