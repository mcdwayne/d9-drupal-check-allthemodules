<?php

namespace Drupal\commerce_approve\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\UncacheableFieldHandlerTrait;
use Drupal\views\ResultRow;
use Drupal\Component\Utility\Html;

/**
 * Defines a form element for approving order items.
 *
 * @ViewsField("commerce_approve")
 */
class Approve extends FieldPluginBase {

  use UncacheableFieldHandlerTrait;

  /**
   * {@inheritdoc}
   */
  public function getValue(ResultRow $row, $field = NULL) {
    return '<!--form-item-' . $this->options['id'] . '--' . $row->index . '-->';
  }

  /**
   * Form constructor for the views form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsForm(array &$form, FormStateInterface $form_state) {
    // Make sure we do not accidentally cache this form.
    $form['#cache']['max-age'] = 0;
    // The view is empty, abort.
    if (empty($this->view->result)) {
      unset($form['actions']);
      return;
    }

    $form[$this->options['id']]['#tree'] = TRUE;
    foreach ($this->view->result as $row_index => $row) {
      $order_items = $row->_entity->getItems();
      /** @var \Drupal\commerce_order\Entity\OrderItem $item */
      foreach ($order_items as $item) {
        /** @var \Drupal\commerce_product\Entity\Product $order_item */
        $order_item = $item->getPurchasedEntity()->getProduct();
        $required = $this->requiresCheckOff($order_item);
        if (!$required) {
          continue;
        }
        if ($required) {
          $ajax_wrapper = Html::getUniqueId('item-approve' . $row_index);

          $form[$this->options['id']][$row_index] = [
            '#prefix' => '<div id="' . $ajax_wrapper . '">',
            '#suffix' => '</div>',
            '#item' => $item,
            '#title' => $required['text'] ?? t('I have verified this product is correct'),
            '#type' => 'checkbox',
            '#name' => 'approve-order-item-' . $row_index,
            '#row_index' => $row_index,
          ];
        }
      }
    }
  }

  /**
   * Submit handler for the views form, adds flag to order item data.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsFormSubmit(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues()['commerce_approve'];

    foreach ($values as $key => $value) {
      /** @var \Drupal\commerce_order\Entity\OrderItem $entity */
      $entity = $form['commerce_approve'][$key]['#item'];
      $entity->setData('approved', TRUE)->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing.
  }

  /**
   * Finds fields referencing terms and check if it requires an approval.
   *
   * @param \Drupal\commerce_product\Entity\Product $order_item
   *   Order item to check.
   *
   * @return bool|array
   *   Array with values if it requires manual approval, FALSE otherwise.
   */
  public function requiresCheckOff(Product $order_item) {
    foreach ($order_item->referencedEntities() as $referencedEntity) {
      $class = get_class($referencedEntity);
      if (strpos($class, 'Term') > -1) {
        if ($referencedEntity->hasField('field_require_approval')) {
          $lock = $referencedEntity->get('field_require_approval');
          if (!empty($lock->getValue()) && $lock->getValue()[0]['value'] == TRUE) {
            return [
              'lock' => TRUE,
              'text' => $referencedEntity->get('field_require_approval_text')->getValue()[0]['value'],
            ];
          }
        }
      }
    }
    return FALSE;
  }

}
