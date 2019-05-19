<?php

namespace Drupal\workflow_task\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'task_state' formatter.
 *
 * @FieldFormatter(
 *   id = "task_state",
 *   label = @Translation("Task state"),
 *   field_types = {
 *     "string",
 *   }
 * )
 */
class TaskStateFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Create an instance of TaskStateFormatter.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /** @var \Drupal\workflow_task\Entity\WorkflowTaskInterface $entity */
    $entity = $items->getEntity();

    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = $entity->getWorkflow();

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $workflow->getTypePlugin()->getState($item->value)->label(),
      ];
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return $field_definition->getName() === 'state' && $field_definition->getTargetEntityTypeId() !== 'workflow_task';
  }

}
