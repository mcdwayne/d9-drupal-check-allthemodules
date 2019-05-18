<?php

namespace Drupal\commerce_inventory_order\Form;

use Drupal\commerce_inventory_order\InventoryOrderManager;
use Drupal\commerce_order\Entity\OrderItemTypeInterface;
use Drupal\commerce_order\Entity\OrderType;
use Drupal\Core\Form\FormStateInterface;
use Drupal\state_machine\Plugin\Workflow\WorkflowTransition;

/**
 * Form alter controller for Order Item Type edit forms.
 *
 * @ingroup commerce_inventory
 */
class OrderItemTypeAlterForm {

  /**
   * Alters the Order Item Type edit form.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public static function alterForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_order\Entity\OrderItemTypeInterface $order_item_type */
    $order_item_type = $form_state->getFormObject()->getEntity();

    // Get State Machine workflow data.
    /** @var \Drupal\state_machine\WorkflowManagerInterface $workflow_manager */
    $workflow_manager = \Drupal::service('plugin.manager.workflow');

    // Get Order Type workflow.
    $order_type_id = $order_item_type->getOrderTypeId();
    $order_type_id = $form_state->getValue(['orderType'], $order_type_id);
    $order_type = OrderType::load($order_type_id);
    /** @var \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $order_type_workflow */
    $order_type_workflow = $workflow_manager->createInstance($order_type->getWorkflowId());

    // Get Order Type adjustment workflow.
    $order_item_workflow_id = InventoryOrderManager::getBundleInventoryWorkflowId($order_item_type);
    $order_item_workflow_id = $form_state->getValue(['inventory_workflow'], $order_item_workflow_id);
    /** @var \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $order_item_workflow */
    $order_item_workflow = $workflow_manager->createInstance($order_item_workflow_id);
    $order_item_workflows = $workflow_manager->getGroupedLabels('commerce_order_item');

    // Setup wrapper Id for transitions.
    $wrapper_id = 'inventory-adjustment-transition-ajax-wrapper';

    // Update transitions with Order Type is changed.
    $form['orderType']['#ajax'] = [
      'callback' => [self::class, 'ajaxRefreshWorkflowTransitions'],
      'wrapper' => $wrapper_id,
    ];

    // Add inventory fieldset.
    $form['inventory'] = [
      '#type' => 'details',
      '#title' => t('Inventory tracking'),
      '#weight' => 15,
      '#open' => TRUE,
      '#collapsible' => FALSE,
      '#tree' => FALSE,
    ];
    $form['inventory']['inventory_intro'] = [
      '#markup' => '<p>' . t('These settings let you control how on-hand and available inventory is tracked, which allows for automatic inventory item holds and adjustments.') . '</p>',
    ];
    $form['inventory']['inventory_workflow'] = [
      '#type' => 'select',
      '#title' => t('Workflow'),
      '#options' => $order_item_workflows,
      '#default_value' => $order_item_workflow_id,
      '#weight' => 0,
      '#required' => TRUE,
      '#ajax' => [
        'callback' => [self::class, 'ajaxRefreshWorkflowTransitions'],
        'wrapper' => $wrapper_id,
      ],
    ];

    // Track Order workflow transitions.
    $form['inventory']['inventory_workflow_transitions'] = [
      '#type' => 'details',
      '#title' => t('Workflow transitions'),
      '#weight' => 10,
      '#open' => TRUE,
      '#collapsible' => FALSE,
      '#tree' => TRUE,
      '#prefix' => '<div id="' . $wrapper_id . '">',
      '#suffix' => '</div>',
      '#wrapper_id' => $wrapper_id,
    ];
    $form['inventory']['inventory_workflow_transitions']['intro'] = [
      '#markup' => '<p>' . t('Automatically adjust available and on-hand inventory when an Order is transitioned.') . '</p>',
    ];

    // Track each Order workflow transition.
    $order_item_transitions = InventoryOrderManager::getBundleInventoryWorkflowTransitions($order_item_type);
    $order_item_transition_labels['_none'] = t('None');
    $order_item_transition_labels += array_map(function (WorkflowTransition $transition) {
      return $transition->getLabel();
    }, $order_item_workflow->getTransitions());

    foreach ($order_type_workflow->getTransitions() as $transition_id => $transition) {
      $form['inventory']['inventory_workflow_transitions'][$transition_id] = [
        '#type' => 'select',
        '#title' => $transition->getLabel(),
        '#options' => $order_item_transition_labels,
        '#default_value' => (array_key_exists($transition_id, $order_item_transitions) ? $order_item_transitions[$transition_id] : NULL),
      ];
    }

    $form['#entity_builders'][] = [self::class, 'buildEntity'];
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   *
   * @return array
   *   A subset of the form to reset.
   */
  public static function ajaxRefreshWorkflowTransitions(array &$form, FormStateInterface $form_state) {
    return $form['inventory']['inventory_workflow_transitions'];
  }

  /**
   * Order Item Type form builder to map values to third party settings.
   *
   * @param string $entity_type
   *   The entity type.
   * @param \Drupal\commerce_order\Entity\OrderItemTypeInterface $order_item_type
   *   The Order Item bundle entity.
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state object.
   */
  public static function buildEntity($entity_type, OrderItemTypeInterface $order_item_type, array &$form, FormStateInterface $form_state) {
    // Get State Machine workflow data.
    /** @var \Drupal\state_machine\WorkflowManagerInterface $workflow_manager */
    $workflow_manager = \Drupal::service('plugin.manager.workflow');

    // Get Order Type workflow.
    $order_type = OrderType::load($order_item_type->getOrderTypeId());
    /** @var \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $workflow */
    $workflow = $workflow_manager->createInstance($order_type->getWorkflowId());

    // Get Order Type adjustment workflow.
    $adjustment_workflow_id = InventoryOrderManager::getBundleInventoryWorkflowId($order_item_type);
    $adjustment_workflow_id = $form_state->getValue(['inventory_workflow'], $adjustment_workflow_id);
    /** @var \Drupal\state_machine\Plugin\Workflow\WorkflowInterface $workflow */
    $adjustment_workflow = $workflow_manager->createInstance($adjustment_workflow_id);

    // Set adjustment workflow.
    $order_item_type->setThirdPartySetting('commerce_inventory_order', 'inventory_workflow', $form_state->getValue('inventory_workflow', 'default'));

    // Track transitions.
    $adjustment_transitions = [];
    foreach ($form_state->getValue('inventory_workflow_transitions') as $order_transition_id => $adjustment_transition_id) {
      if ($workflow->getTransition($order_transition_id) && $adjustment_workflow->getTransition($adjustment_transition_id)) {
        $adjustment_transitions[$order_transition_id] = $adjustment_transition_id;
      }
    }
    $order_item_type->setThirdPartySetting('commerce_inventory_order', 'inventory_workflow_transitions', $adjustment_transitions);
  }

}
