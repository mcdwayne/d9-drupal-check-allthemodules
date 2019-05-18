<?php

namespace Drupal\iu_paragraphs\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an 'Entity type' condition.
 *
 * Note that this condition does not work in node_preview and possibly in
 * node_revision contexts without the patch on core issue:
 * https://www.drupal.org/i/2890758
 *
 * @Condition(
 *   id = "entity_type",
 *   label = @Translation("Entity type"),
 * )
 */
class EntityType extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * An array of plugin definitions (empty array if no definitions were found). Keys are plugin IDs.
   *
   * @var mixed[]
   */
  protected $entityTypeDefinitions;

  /**
   * Creates a new EntityType instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
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
  public function __construct(EntityTypeManagerInterface $entity_type_manager, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeDefinitions = $entity_type_manager->getDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity_type.manager'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    foreach ($this->entityTypeDefinitions as $type) {
      $options[$type->id()] = $type->getLabel();
    }

    $form['types'] = [
      '#title' => $this->pluginDefinition['label'],
      '#type' => 'checkboxes',
      '#options' => $options,
      '#default_value' => $this->configuration['types'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['types'] = array_filter($form_state->getValue('types'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['types' => []] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['types']) && !$this->isNegated()) {
      return TRUE;
    }
    foreach ($this->configuration['types'] as $type) {
      if ($entity_id = \Drupal::routeMatch()->getParameter($type)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $types = $this->configuration['types'];
    if (empty($types)) {
      return;
    }

    foreach ($this->entityTypeDefinitions as $type) {
      if (in_array($type->id(), $types)) {
        $labels[] = $type->getLabel();
      }
    }

    if (count($labels) > 1) {
      $last = array_pop($labels);
      $labels = implode(', ', $labels);

      if (!empty($this->configuration['negate'])) {
        return $this->t('Entity type is not @types or @last', array('@types' => $labels, '@last' => $last));
      }
      return $this->t('Entity type is @types or @last', array('@types' => $labels, '@last' => $last));
    }
    $label = reset($labels);

    if (!empty($this->configuration['negate'])) {
      return $this->t('Entity type is not @type', array('@type' => $label));
    }
    return $this->t('Entity type is @type', array('@type' => $label));
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'url';
    return $contexts;
  }

}
