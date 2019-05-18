<?php
/**
 * Displays information about a specific payment.
 * @author appels
 */

namespace Drupal\adcoin_payments\Controller;
use Drupal\adcoin_payments\Model\PaymentStorage;
use Drupal\Core\Controller\ControllerBase;

class ViewPaymentController extends ControllerBase {
  public function content($payment_id = NULL) {
    // Fetch payment record data
    $payment = PaymentStorage::paymentFetch($payment_id);

    // Construct the table header
    $header = [
      ['data' => t('Field')],
      ['data' => t('Value')]
    ];

    // Construct table rows
    $rows[] = [ 'field' => 'Name'      , 'value' => $payment['name'] ];
    $rows[] = [ 'field' => 'Email'     , 'value' => $payment['email'] ];
    $rows[] = [ 'field' => 'Postal'    , 'value' => $payment['postal'] ];
    $rows[] = [ 'field' => 'Phone'     , 'value' => $payment['phone'] ];
    $rows[] = [ 'field' => 'Country'   , 'value' => $payment['country'] ];
    $rows[] = [ 'field' => 'Amount'    , 'value' => $payment['amount'] . ' ACC' ];
    $rows[] = [ 'field' => 'Created At', 'value' => format_date(strtotime($payment['created_at']), 'custom', 'j F Y') ];

    // Payment information
    $build['payment_info'] = [
      '#type'   => 'table',
      '#header' => $header,
      '#rows'   => $rows,
      '#empty'  => t('No payments found.')
    ];

    // Status dropdown element
    $form = \Drupal::service('class_resolver')->getInstanceFromDefinition('\Drupal\adcoin_payments\Form\ViewPaymentForm');
    $form->setPaymentId($payment_id);
    $build['status_picker'] = \Drupal::formBuilder()->getForm($form);

    return $build;
  }
}