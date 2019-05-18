<?php

namespace Drupal\Tests\contacts_events\Kernel;

use Drupal\contacts_events\Entity\EventClassInterface;
use Drupal\rules\Context\ContextConfig;

/**
 * Helper trait for adding conditions to event classes.
 */
trait EventClassConditionTrait {

  /**
   * Helper to add a date based condition.
   *
   * @param \Drupal\contacts_events\Entity\EventClassInterface $class
   *   The class to add the condition to.
   * @param string|null $min
   *   The inclusive minimum difference between the dates, as a DateInterval
   *   definition.
   * @param string|null $max
   *   The non-inclusive minimum difference between the dates, as a DateInterval
   *   definition.
   * @param array $source
   *   The source field, e.g. a date of birth. Keys are:
   *   - 0: Entity path.
   *   - 1: Field name.
   *   - 2: Property (defaults to 'date').
   * @param array $comparison
   *   The comparison field, e.g. a date of event. Keys are:
   *   - 0: Entity path.
   *   - 1: Field name.
   *   - 2: Property (defaults to 'date').
   *
   * @throws \Drupal\rules\Exception\LogicException
   */
  protected function addClassDateCondition(EventClassInterface $class, $min, $max, array $source = NULL, array $comparison = NULL) {
    if (empty($source)) {
      $source = [
        'order_item.purchased_entity.entity',
        'date_of_birth',
        'date',
      ];
    }
    if (empty($comparison)) {
      $comparison = [
        'order_item.purchased_entity.entity.event.entity',
        'date',
        'start_date',
      ];
    }
    list($source_entity_path, $source_field_name) = $source;
    $source_sub_property = $source[2] ?? 'date';
    $source_field_path = "{$source_entity_path}.{$source_field_name}";

    list($comparison_entity_path, $comparison_field_name) = $comparison;
    $comparison_sub_property = $comparison[2] ?? 'date';
    $comparison_field_path = "{$comparison_entity_path}.{$comparison_field_name}";

    // Check the source field exists and has a value.
    $context_config = ContextConfig::create()
      ->map('entity', $source_entity_path)
      ->setValue('field', $source_field_name);
    $class->addCondition('rules_entity_has_field', $context_config);

    $context_config = ContextConfig::create()
      ->map('data', "{$source_field_path}.0")
      ->negateResult();
    $class->addCondition('rules_data_is_empty', $context_config);

    // Check the comparison field exists and has a value.
    $context_config = ContextConfig::create()
      ->map('entity', $comparison_entity_path)
      ->setValue('field', $comparison_field_name);
    $class->addCondition('rules_entity_has_field', $context_config);

    $context_config = ContextConfig::create()
      ->map('data', "{$comparison_field_path}.0")
      ->negateResult();
    $class->addCondition('rules_data_is_empty', $context_config);

    if (isset($min)) {
      $context_config = ContextConfig::create()
        ->map('data', "{$source_field_path}.{$source_sub_property}")
        ->map('value', "{$comparison_field_path}.{$comparison_sub_property}")
        // Use > with negate to achieve <=.
        ->setValue('operation', '>')
        ->negateResult()
        ->process('value', 'rules_dynamic_date', [
          'interval' => $min,
          'normalize' => TRUE,
        ]);
      $class->addCondition('rules_data_comparison', $context_config);
    }

    if (isset($max)) {
      $context_config = ContextConfig::create()
        ->map('data', "{$source_field_path}.{$source_sub_property}")
        ->map('value', "{$comparison_field_path}.{$comparison_sub_property}")
        ->setValue('operation', '>')
        ->process('value', 'rules_dynamic_date', [
          'interval' => $max,
          'normalize' => TRUE,
        ]);
      $class->addCondition('rules_data_comparison', $context_config);
    }
  }

}
