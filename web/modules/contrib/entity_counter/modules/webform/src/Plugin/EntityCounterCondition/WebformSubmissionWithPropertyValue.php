<?php

namespace Drupal\entity_counter_webform\Plugin\EntityCounterCondition;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\entity_counter\Plugin\EntityCounterConditionBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the has property value condition for webform submissions.
 *
 * @EntityCounterCondition(
 *   id = "webform_submission_with_property_value",
 *   label = @Translation("Webform submission with property value"),
 *   category = @Translation("Webform submission"),
 *   entity_type = "webform_submission",
 * )
 */
class WebformSubmissionWithPropertyValue extends EntityCounterConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a WebformSubmissionWithValue object.
   *
   * @param array $configuration
   *   The plugin configuration.
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
      'field_name' => NULL,
      'operator' => NULL,
      'field_value' => NULL,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $options = [];
    foreach ($this->entityFieldManager->getBaseFieldDefinitions('webform_submission') as $field_name => $field_definition) {
      $options[$field_name] = $field_definition->getLabel();
    }

    $form['field_name'] = [
      '#type' => 'select',
      '#title' => $this->t('Field'),
      '#options' => $options,
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => empty($this->configuration['field_name']) ? NULL : $this->configuration['field_name'],
      '#required' => TRUE,
    ];
    $form['operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Comparison operator'),
      '#options' => $this->getComparisonOperators(),
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => empty($this->configuration['operator']) ? NULL : $this->configuration['operator'],
      '#required' => TRUE,
    ];
    $form['field_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#default_value' => isset($this->configuration['field_value']) ? $this->configuration['field_value'] : NULL,
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $this->configuration['field_name'] = $values['field_name'];
    $this->configuration['operator'] = $values['operator'];
    $this->configuration['field_value'] = $values['field_value'];
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate(EntityInterface $entity) {
    /** @var \Drupal\webform\WebformSubmissionInterface $entity */
    $this->assertEntity($entity);

    if ($entity->hasField($this->configuration['field_name'])) {
      $value = $entity->get($this->configuration['field_name'])->value;
      switch ($this->configuration['operator']) {
        case '<':
          return $value < $this->configuration['field_value'];

        case '<=':
          return $value <= $this->configuration['field_value'];

        case '>=':
          return $value >= $this->configuration['field_value'];

        case '>':
          return $value > $this->configuration['field_value'];

        case '==':
          return $value == $this->configuration['field_value'];
      }
    }

    return FALSE;
  }

}
