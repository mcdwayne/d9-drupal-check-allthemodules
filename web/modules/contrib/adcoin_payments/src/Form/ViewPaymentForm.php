<?php
/**
 * This form displays the information of a specific payment record.
 * @author appels
 */

namespace Drupal\adcoin_payments\Form;
use Drupal\adcoin_payments\Model\PaymentStorage;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\Element;


class ViewPaymentForm extends FormBase {

  public $payment_id;

  public function setPaymentId($payment_id) {
    $this->payment_id = $payment_id;
  }



  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'view_payment_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $payment = PaymentStorage::paymentFetch($this->payment_id);

    $form['status'] = [
      '#type'    => 'select',
      '#name'    => 'status',
      '#title'   => t('Payment Status'),
      '#options' => [
        '0' => PaymentStorage::getStatusText(0),
        '1' => PaymentStorage::getStatusText(1),
        '2' => PaymentStorage::getStatusText(2),
        '3' => PaymentStorage::getStatusText(3),
        '4' => PaymentStorage::getStatusText(4)
      ],
      '#required'      => TRUE,
      '#default_value' => $payment['status']
    ];

    $form['actions']['submit'] = [
      '#type'          => 'submit',
      '#default_value' => t('Update Status'),
      '#button_type'   => 'primary'
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    PaymentStorage::paymentUpdateStatus($this->payment_id, (int)$_POST['status']);
  }
}