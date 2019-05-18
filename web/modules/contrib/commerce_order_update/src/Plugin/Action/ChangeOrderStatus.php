<?php

namespace Drupal\commerce_order_update\Plugin\Action;

use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Change Order Status.
 *
 * @Action(
 *   id = "change_order_status",
 *   label = @Translation("Change Order Status"),
 *   type = "commerce_order"
 * )
 */
class ChangeOrderStatus extends ViewsBulkOperationsActionBase implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $workflow_manager = \Drupal::service('plugin.manager.workflow');
    $wm = $workflow_manager->createInstance('order_fulfillment_validation');
    $states = $wm->getStates();
    foreach ($states as $state_id => $state) {
      $values[$state_id] = $state->getLabel();
    }

    $form['order_state'] = [
      '#title' => t('Order State'),
      '#type' => 'select',
      '#description' => "Select the order state.",
      '#options' => $values,
      '#default_value' => $form_state->getValue('order_state'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $value = $this->configuration['change_order_status'] = $form_state->getValue('order_state');
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    if ($this->configuration['change_order_status']) {
      $entity->set('state', $this->configuration['change_order_status']);
      $entity->save();
    }

  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    // @var /Drupal\commerce_order\Entity\Order $object.
    $result = $object->access('update', $account, TRUE);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
