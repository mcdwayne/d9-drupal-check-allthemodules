<?php

namespace Drupal\uc_payment_pack\Plugin\Ubercart\PaymentMethod;

use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\PaymentMethodPluginBase;

/**
 * Defines the cash on delivery payment method.
 *
 * @UbercartPaymentMethod(
 *   id = "cod",
 *   name = @Translation("Cash on delivery"),
 * )
 */
class CashOnDelivery extends PaymentMethodPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'policy' => 'Full payment is expected upon delivery or prior to pick-up.',
      'max_order' => 0,
      'delivery_date' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['policy'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Policy message'),
      '#default_value' => $this->configuration['policy'],
      '#description' => $this->t('Help message shown at checkout.'),
    ];

    $form['max_order'] = [
      '#type' => 'uc_price',
      '#title' => $this->t('Maximum order total eligible for COD'),
      '#default_value' => $this->configuration['max_order'],
      '#description' => $this->t('Set to 0 for no maximum order limit.'),
    ];

    $form['delivery_date'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Let customers enter a desired delivery date.'),
      '#default_value' => $this->configuration['delivery_date'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['policy'] = $form_state->getValue('policy');
    $this->configuration['max_order'] = $form_state->getValue('max_order');
    $this->configuration['delivery_date'] = $form_state->getValue('delivery_date');
  }

  /**
   * {@inheritdoc}
   */
  public function cartDetails(OrderInterface $order, array $form, FormStateInterface $form_state) {
    $build['#attached']['library'][] = 'uc_payment_pack/cod.styles';
    $build['policy'] = [
      '#prefix' => '<p>',
      '#markup' => Html::escape($this->configuration['policy']),
      '#suffix' => '</p>',
    ];

    if (($max = $this->configuration['max_order']) > 0 && is_numeric($max)) {
      $build['eligibility'] = [
        '#prefix' => '<p>',
        '#markup' => $this->t('Orders totalling more than @amount are <b>not eligible</b> for COD.', ['@amount' => uc_currency_format($max)]),
        '#suffix' => '</p>',
      ];
    }

    if ($this->configuration['delivery_date']) {
      $build += $this->deliveryDateForm($order);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function cartProcess(OrderInterface $order, array $form, FormStateInterface $form_state) {
    if ($this->configuration['delivery_date']) {
      $order->payment_details = $form_state->getValue(['panes', 'payment', 'details']);
      if (isset($order->payment_details['delivery_date'])) {
        $order->payment_details['delivery_date'] = $order->payment_details['delivery_date']->getTimestamp();
      }
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function cartReview(OrderInterface $order) {
    $review = [];

    if ($this->configuration['delivery_date'] &&
        isset($order->payment_details['delivery_date'])) {
      $date = \Drupal::service('date.formatter')->format($order->payment_details['delivery_date'], 'uc_store');

      $review[] = ['title' => $this->t('Delivery date'), 'data' => $date];
    }

    return $review;
  }

  /**
   * {@inheritdoc}
   */
  public function orderView(OrderInterface $order) {
    $build = [];

    if ($this->configuration['delivery_date'] &&
        isset($order->payment_details['delivery_date'])) {
      $build['#markup'] = $this->t('Desired delivery date:') . '<br />' . \Drupal::service('date.formatter')->format($order->payment_details['delivery_date'], 'uc_store');
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function orderEditDetails(OrderInterface $order) {
    $build = [];

    if ($this->configuration['delivery_date']) {
      $build = $this->deliveryDateForm($order);
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function orderEditProcess(OrderInterface $order, array $form, FormStateInterface $form_state) {
    if ($this->configuration['delivery_date']) {
      $payment_details = $form_state->getValue('payment_details');
      if (isset($payment_details)) {
        $payment_details['delivery_date'] = $payment_details['delivery_date']->getTimestamp();
        return $payment_details;
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function orderLoad(OrderInterface $order) {
    $result = db_query('SELECT * FROM {uc_payment_cod} WHERE order_id = :id', [':id' => $order->id()]);
    if ($row = $result->fetchObject()) {
      $order->payment_details['delivery_date'] = $row->delivery_date;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function orderSave(OrderInterface $order) {
    if (isset($order->payment_details['delivery_date'])) {
      db_merge('uc_payment_cod')
        ->key(['order_id' => $order->id()])
        ->fields([
          'delivery_date' => $order->payment_details['delivery_date'],
        ])
        ->execute();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function orderSubmit(OrderInterface $order) {
    $max = $this->configuration['max_order'];

    if ($max > 0 && $order->getTotal() > $max) {
      return $this->t('Your final order total exceeds the maximum for COD payment. Please go back and select a different method of payment.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function orderDelete(OrderInterface $order) {
    db_delete('uc_payment_cod')
      ->condition('order_id', $order->id())
      ->execute();
  }

  /**
   * Collect additional information for the "Cash on Delivery" payment method.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order entity.
   */
  protected function deliveryDateForm(OrderInterface $order) {
    $delivery_date = empty($order->payment_details['delivery_date']) ?
                     DrupalDateTime::createFromTimestamp(\Drupal::time()->getRequestTime()) :
                     DrupalDateTime::createFromTimestamp($order->payment_details['delivery_date']);

    $form['delivery_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Enter a desired delivery date:'),
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
      '#prefix' => '<div>',
      '#suffix' => '</div>',
      '#default_value' => $delivery_date,
    ];

    return $form;
  }

}
