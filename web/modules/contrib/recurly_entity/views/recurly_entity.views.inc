<?php

/**
 * Implements hook_views_data_alter().
 */
function recurly_entity_views_data_alter(&$data) {
  $info = entity_get_info();

  foreach (array_keys($info) as $entity_type) {
    // Don't allow a relationship to ourselves.
    if ($entity_type == 'recurly_adjustment') {
      continue;
    }

    $data['recurly_adjustment'][$entity_type] = _recurly_entity_relationship_data('Recurly adjustment', $entity_type);
  }

  // The relationship between Subscriptions and Subscription Plans uses the
  // plan code, and not the entity ID, so we have to alter the keys.
  $data['recurly_subscription']['plan_code'] = [
    'relationship' => [
      'label' => 'Subscription Plan',
      'handler' => 'views_handler_relationship',
      'base' => 'recurly_subscription_plan',
      'base field' => 'plan_code',
      'relationship field' => 'plan_code',
    ],
    'title' => 'Plan code',
    'help' => 'Subscription "plan_code" property.',
    'field' => [
      'real field' => 'plan_code',
      'handler' => 'views_handler_field_numeric',
      'click sortable' => TRUE,
      'float' => FALSE,
    ],
    'sort' => [
      'real field' => 'plan_code',
      'handler' => 'views_handler_sort',
    ],
    'filter' => [
      'real field' => 'plan_code',
      'handler' => 'views_handler_filter_numeric',
    ],
    'argument' => [
      'real field' => 'plan_code',
      'handler' => 'views_handler_argument_numeric',
    ],
  ];
  return $data;
}

