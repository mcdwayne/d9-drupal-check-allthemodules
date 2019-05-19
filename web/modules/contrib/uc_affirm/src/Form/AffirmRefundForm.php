<?php

/**
 * @file
 * Contains \Drupal\uc_affirm\Form\AffirmRefundForm.
 */

namespace Drupal\uc_affirm\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\uc_order\Entity\Order;

/**
 * Affirm refund form.
 */
class AffirmRefundForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_form_refund_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Retrieve an array which contains the path pieces.
    $current_path = \Drupal::service('path.current')->getPath();
    $path_args = explode('/', $current_path);
    $order_id = $path_args[4];
    $order = Order::load($order_id);

    // Getting the order date from time stamp fromat.
    $created_date = $order->getCreatedTime();
    $created_date = format_date($created_date, $type = 'html_date', $format = 'Y-m-d', $timezone = NULL, $langcode = NULL);

    // Getting the last date of refund.
    $date = date_create($created_date);
    date_add($date, date_interval_create_from_date_string('120 days'));
    $refundable_date = date_format($date, 'Y-m-d');

    // Checking the current date is greater than the refundable date.
    if (date("Y-m-d") > $refundable_date) {
      drupal_set_message(t('You cannot make a refund againt this order! Refund date expired!'), 'error');
      $form_state->setRedirect('uc_order.order_admin');
      return;
    }

    $refund_data = db_query("SELECT * FROM {uc_affirm} WHERE order_id = :id", array(':id' => $order_id))->fetchObject();
    $default_amount = $order->getTotal() - $refund_data->refund_amount;
    
    if ($default_amount <= 0) {
      drupal_set_message(t('You cannot make a refund againt this order!'), 'error');
      $form_state->setRedirect('uc_order.order_admin');
      return;
    }
    else {
      $form['refund']['amount'] = array(
        '#type' => 'textfield',
        '#title' => t('Refund amount'),
        '#description' => t('Enter the amount to be credited back to the original credit card(Doller).'),
        '#default_value' => $default_amount,
        '#size' => 16,
      );
      $form['refund']['order_id'] = array(
        '#type' => 'hidden',
        '#default_value' => $order_id,
        '#size' => 16,
      );
      $form['refund']['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Submit'),
      );

      return $form;
    }
    
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $refund_amount = $form_state->getValue('amount');
    $order_id = $form_state->getValue('order_id');
    $order = Order::load($order_id);
    $refund_data = db_query("SELECT * FROM {uc_affirm} WHERE order_id = :id", array(':id' => $order_id))->fetchObject();
    // Ensure a positive numeric amount has been entered for refund.
    if (!is_numeric($refund_amount) || $refund_amount <= 0) {
      $form_state->setErrorByName('amount', $this->t("You must specify a positive numeric amount to refund.", array()));
    }

    // Ensure the amount is less than or equal to the captured amount.
    if ($refund_data->refund_amount + $refund_amount > $order->getTotal()) {
      $form_state->setErrorByName('amount', $this->t('You cannot refund more than you captured.', array()));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $acontroller = new \Drupal\uc_affirm\Controller\AffirmController();
    $order_id = $form_state->getValue('order_id');
    $refund_amount = $form_state->getValue('amount');
    $order = Order::load($order_id);
    $txn_type = UC_AFFIRM_REFUND;
    $charge_id = _get_uc_affirm_charge_id($order_id);
    $data = array(
      'amount' => number_format((float) $refund_amount, 2, '.', '') * 100,
    );

    $response = $acontroller->uc_affirm_api_request($txn_type, $order, $charge_id, $data);
    if ($response) {
      if (isset($response['status_code'])) {
        drupal_set_message(t('Refund failed'), 'error');
      }
      else {
        drupal_set_message(t('Refund for @amount issued successfully.', array('@amount' => $refund_amount, '@currency' => 'USD')));
        $refund_data = db_query("SELECT * FROM {uc_affirm} WHERE order_id = :id", array(':id' => $order_id))->fetchObject();
        // Caluculating the refund amount.
        $refund_amount = $refund_data->refund_amount + $refund_amount;
        // update status on uc_order table.
        $acontroller->uc_afffirm_transaction_updates($order_id, $response, $status = 'chargeback', $field = 'refund_trans_id', $refund_amount);
        // Save the comment.
        $message =  $this->t('Refund of @amount @currency submitted through Affirm.', array('@amount' => $refund_amount, '@currency' => 'USD'));
        uc_order_comment_save($order_id, 0, $message, 'admin');
      }
    }
    $form_state->setRedirect('uc_order.order_admin');
  }

}
