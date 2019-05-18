<?php

namespace Drupal\block_visibility_conditions\Plugin\Condition;

use Drupal;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Not Node Type' condition. The block will still be shown on all
 * other pages, including non-content types. This differs from the negated
 * condition "Content types", which will only be evaluated on node pages, which
 * means the block won't be shown on other pages like views.
 *
 * @Condition(
 *   id = "not_node_type",
 *   label = @Translation("Not Node Type")
 * )
 */
class NotNodeType extends ConditionPluginBase {

  /**
   * Creates a new NotNodeType instance.
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Disallow negation of this condition.
    unset($form['negate']);

    // Create list of content types.
    $options = [];
    $node_types = Drupal::service('entity.manager')
      ->getStorage('node_type')
      ->loadMultiple();
    foreach ($node_types as $type) {
      $options[$type->id()] = $type->label();
    }
    $form['bundles'] = [
      '#title' => $this->t('Node types'),
      '#description' => $this->t('The content types to hide the block on. The block will still be shown on all other pages, including non-content types.<br>This differs from the negated condition "Content types", which will only be evaluated on node pages, which means the block won\'t be shown on other pages like views.'),
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $this->configuration['bundles'],
    ];

    return $form;
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
      return $this->t('The node bundle is @bundles or @last', [
        '@bundles' => $bundles,
        '@last' => $last,
      ]);
    }
    $bundle = reset($this->configuration['bundles']);
    return $this->t('The node bundle is @bundle', ['@bundle' => $bundle]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // Check if a setting has been set.
    if (empty($this->configuration['bundles'])) {
      return TRUE;
    }

    // Check if we are dealing with a node.
    $node = \Drupal::routeMatch()->getParameter('node');
    if (is_scalar($node)) {
      $node = Node::load($node);
    }

    if (empty($node)) {
      return TRUE;
    }

    return empty($this->configuration['bundles'][$node->getType()]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['bundles' => []] + parent::defaultConfiguration();
  }
}
