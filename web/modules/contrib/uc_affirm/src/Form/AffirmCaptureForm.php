<?php

namespace Drupal\uc_affirm\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\Entity\Order;

/**
 * Defines a confirmation form for void the order.
 */
class AffirmCaptureForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_form_capture_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return t('Are you sure to want capture this order?', array());
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
    $current_path = \Drupal::service('path.current')->getPath();
    $path_args = explode('/', $current_path);
    $order_id = $path_args[4];
    $order = Order::load($order_id);
    
    $txn_type = UC_AFFIRM_CAPTURE_ONLY;
    $charge_id = _get_uc_affirm_charge_id($order_id);
    $data = array(
      'order_id' => $order_id,
    );
    $response = $acontroller->uc_affirm_api_request($txn_type, $order, $charge_id, $data);
    if (empty($response)) {
      drupal_set_message(t('We could not complete your payment with Affirm. Please try again or contact us if the problem persists.'), 'error');
      return FALSE;
    }
    else {
      if (isset($response['status_code'])) {
        if ($response['code'] == 'capture-unauthorized') {
          drupal_set_message(t('Unauthorized capture request.'), 'error');
        }
      }
      elseif ($response['code'] == 'capture-declined') {
        drupal_set_message(t('Affirm amount capture failed, Please try agin !!'), 'error');
      }
      else {
        drupal_set_message(t('Captured successfully.'));
        $acontroller->uc_afffirm_transaction_updates($order_id, $response, $status = 'payment_received', $field = 'capture_trans_id');
        uc_order_comment_save($order_id, 0, t('Payment of @amount @currency submitted through Affirm.', array('@amount' => $order->order_total, '@currency' => 'USD')), 'order', $status = 'payment_received');
      }
    }
    $form_state->setRedirect('uc_order.order_admin');
  }

}