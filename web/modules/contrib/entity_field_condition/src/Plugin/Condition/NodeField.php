<?php

namespace Drupal\entity_field_condition\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;

/**
 * Provides a 'Node Field' condition.
 *
 * @Condition(
 *   id = "node_field",
 *   label = @Translation("Node Field"),
 *   context = {
 *     "node" = @ContextDefinition(
 *       "entity:node",
 *       required = TRUE,
 *       label = @Translation("node")
 *     )
 *   }
 * )
 */
class NodeField extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Drupal\Core\Field\FieldTypePluginManagerInterface definition.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * Creates a new NodeField instance.
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
   *   The entity type manager interface.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager interface.
   * @param Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field type plugin manager interface.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, FieldTypePluginManagerInterface $field_type_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.field.field_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['entity_bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Node type'),
      '#options' => $this->getNodeTypes(),
      '#validated' => TRUE,
      '#ajax' => [
        'callback' => [$this, 'fieldsCallback'],
        'wrapper' => 'field-wrapper',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Loading fields...'),
        ],
      ],
      '#default_value' => $this->configuration['entity_bundle'],
    ];

    // Load fields based on the selected entity_bundle.
    $form['field'] = [
      '#type' => 'select',
      '#prefix' => '<div id="field-wrapper">',
      '#suffix' => '</div>',
      '#title' => $this->t('Field'),
      '#validated' => TRUE,
      '#options' => $this->getNodeFields($this->configuration['entity_bundle']),
      '#default_value' => $this->configuration['field'],
    ];

    $form['value_source'] = [
      '#type' => 'select',
      '#title' => $this->t('Value Source'),
      '#options' => [
        'null' => $this->t('Is NULL'),
        'specified' => $this->t('Specified'),
      ],
      '#default_value' => $this->configuration['value_source'],
    ];

    $form['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value to be compared'),
      '#default_value' => $this->configuration['value'],
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * Return the node types.
   *
   * @return array
   *   Returns the available node types.
   */
  protected function getNodeTypes() {
    // Get all the Node types.
    $node_types = $this->entityTypeManager->getStorage('node_type')
      ->loadMultiple();

    // Options for node types.
    $node_type_options = $this->getEmptyOption();

    foreach ($node_types as $node_type) {
      // Adding the nodes types.
      $node_type_options[$node_type->id()] = $node_type->label();
    }

    return $node_type_options;
  }

  /**
   * Return the empty option for the select elements.
   *
   * @return array
   *   Returns the empty option for the select elements.
   */
  public function getEmptyOption() {
    return ['' => $this->t('None')];
  }

  /**
   * Return the fields for a content type.
   *
   * @param string $node_type
   *   The node type machine name.
   *
   * @return array
   *   Returns the available fields for the content type.
   */
  protected function getNodeFields($node_type) {
    $labels = $this->getEmptyOption();

    if (empty($node_type)) {
      return $labels;
    }

    // Getting the fields for the content type.
    $node_fields = $this->entityFieldManager->getFieldDefinitions('node', $node_type);

    // Getting the field definitions.
    $field_types = $this->fieldTypePluginManager->getDefinitions();

    foreach ($node_fields as $field) {
      // Get the field type label.
      $field_type_label = $field_types[$field->getType()]['label']->getUntranslatedString();
      $labels[$field_type_label][$field->getName()] = $field->getLabel();
    }

    return $labels;
  }

  /**
   * Handles switching the available fields.
   *
   * Handles switching the available fields based on the selected content type
   * (node type).
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Returns the available fields for the selected content type.
   */
  public function fieldsCallback(array $form, FormStateInterface $form_state) {
    // Getting the node type.
    $node_type = $form_state->getValues()['visibility']['node_field']['entity_bundle'];

    // Adding the content type fields.
    $form['visibility']['node_field']['field']['#options'] = $this->getNodeFields($node_type);

    return $form['visibility']['node_field']['field'];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Get fields values.
    $entity_bundle = $form_state->getValue('entity_bundle');
    $field = $form_state->getValue('field');

    // Check validation.
    if ($entity_bundle && empty($field)) {
      $form_state->setErrorByName('field', $this->t('If you select a node type, you must specify a field.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['entity_bundle'] = $form_state->getValue('entity_bundle');
    $this->configuration['field'] = $form_state->getValue('field');

    $this->configuration['value_source'] = $form_state->getValue('value_source');
    $this->configuration['value'] = $form_state->getValue('value');

    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = [
      'entity_type_id' => 'node',
      'entity_bundle' => '',
      'field' => '',
      'value_source' => 'null',
      'value' => '',
    ];

    return $configuration + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (empty($this->configuration['field']) && !$this->isNegated()) {
      return TRUE;
    }

    $entity_type_id = $this->configuration['entity_type_id'];
    $entity_bundle = $this->configuration['entity_bundle'];
    $field = $this->configuration['field'];

    $entity = $this->getContextValue($entity_type_id);

    if (is_subclass_of($entity, 'Drupal\Core\Entity\ContentEntityBase') && $entity->getEntityTypeId() === $entity_type_id && $entity->getType() === $entity_bundle) {
      $value = $entity->get($field)->getValue();

      $value_to_compare = NULL;

      // Structured data.
      if (is_array($value)) {
        if (!empty($value)) {
          // Loop through each value and compare.
          foreach ($value as $value_item) {
            // Check for target_id to support references.
            if (isset($value_item['target_id'])) {
              $value_to_compare = $value_item['target_id'];
            }
            // Check for uri to support links.
            else if (isset($value_item['uri'])) {
              $value_to_compare = $value_item['uri'];
            }
            else {
              $value_to_compare = $value_item['value'];
            }
            // Return comparison only if true.
            if ($value_to_compare === $this->configuration['value']) {
              return TRUE;
            }
          }
        }
      }
      // Default.
      else {
        $value_to_compare = $value;
      }

      // Compare if null.
      if ($this->configuration['value_source'] === 'null') {
        return is_null($value_to_compare);
      }
      // Regular comparison.
      return $value_to_compare === $this->configuration['value'];
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    // Entity Type.
    $entity_type_id = $this->configuration['entity_type_id'];
    $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type_id);

    // Entity Bundle.
    $entity_bundle = $this->configuration['entity_bundle'];

    // Field.
    $field = $this->configuration['field'];

    // Get Field label.
    foreach ($this->entityFieldManager->getFieldDefinitions($entity_type_id, $entity_bundle) as $field_definition) {
      if ($field_definition->getName() === $field) {
        $field_label = (string) $field_definition->getLabel();
      }
    }

    return t('@entity_type "@entity_bundle" field "@field" is "@value"', [
      '@entity_type' => $entity_type_definition->getLabel(),
      '@entity_bundle' => $entity_bundle,
      '@field' => $field_label,
      '@value' => $this->configuration['value_source'] === 'null' ? 'is NULL' : $this->configuration['value'],
    ]);
  }

}
