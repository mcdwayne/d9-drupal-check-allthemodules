<?php

namespace Drupal\uc_affirm\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\Entity\Order;

/**
 * Defines a confirmation form for void the order.
 */
class AffirmVoidForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_form_void_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure to want void this order?', array());
  }

  /**
   * {@inheritdoc}
   */
    public function getCancelUrl() {
      return new Url('uc_order.order_admin');
  }

  /**
   * {@inheritdoc}
   */
    public function getDescription() {
    return t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
    public function getConfirmText() {
    return t('Accept');
  }

  /**
   * {@inheritdoc}
   */
    public function getCancelText() {
    return t('Cancel');
  }

  /**
   * {@inheritdoc}
   *
   * @param int $id
   *   (optional) The ID of the item to be deleted.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $pid = NULL) {
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $acontroller = new \Drupal\uc_affirm\Controller\AffirmController();
    
    // Retrieve an array which contains the path pieces.
    $current_path = \Drupal::service('path.current')->getPath();
    $path_args = explode('/', $current_path);
    $order_id = $path_args[4];
    $order = Order::load($order_id);
    
    // Get charge_id from the order id.
    $charge_id = _get_uc_affirm_charge_id($order_id);
    $response = $acontroller->uc_affirm_void($charge_id);

    // The call is valid and the payment gateway has been approved.
    if ($response) {
      if (isset($response['status_code'])) {
        drupal_set_message(t('Void failed try again.'), 'error');
      }
      else {
        // update status on uc_order table.
        $acontroller->uc_afffirm_transaction_updates($order_id, $response, $status = 'canceled', $field = 'void_trans_id');
        uc_order_comment_save($order_id, 0, t('The order canceled.'), 'order', $status = 'canceled');
        drupal_set_message(t('Transaction successfully voided.'));
      }
    }
    $form_state->setRedirect('uc_order.order_admin');
  }

}