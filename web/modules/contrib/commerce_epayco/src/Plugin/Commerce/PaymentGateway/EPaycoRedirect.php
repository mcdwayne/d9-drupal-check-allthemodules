<?php

namespace Drupal\commerce_epayco\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_epayco\Entity\CommerceEpaycoApiData as ePaycoConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "epayco_offsite_redirect",
 *   label = "ePayco (Off-site)",
 *   display_label = "ePayco (Off-site)",
 *   modes = {
 *     "n/a" = @Translation("N/A"),
 *   },
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_epayco\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class EPaycoRedirect extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $config_entity = isset($this->configuration['configuration']) ? ePaycoConfig::load($this->configuration['configuration']) : NULL;

    $form['guidelines'] = [
      '#markup' => $this->t('Please, read carefully documentation at @docs-link.', [
        '@docs-link' => Link::fromTextAndUrl(
          'http://epayco.co/docs/standard_checkout',
          Url::fromUri('http://epayco.co/docs/standard_checkout')
        )->toString(),
      ]),
    ];
    $form['configuration'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Configuration entity'),
      '#description' => $this->t('Select one of the configuration entities from @configs-link.', [
        '@configs-link' => Link::fromTextAndUrl(
          'admin/commerce/config/commerce-epayco/api-data',
          Url::fromRoute('entity.commerce_epayco_api_data.list')
        )->toString(),
      ]),
      '#target_type' => 'commerce_epayco_api_data',
      '#default_value' => $config_entity,
      '#required' => TRUE,
    ];
    // @TODO: Is this feature really needed?.
    $form['p_confirm_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Confirm method'),
      '#options' => [
        'POST' => 'POST',
        'GET' => 'GET',
      ],
      '#description' => $this->t('This is also known as "@cm".', ['@cm' => 'p_confirm_method']),
      '#default_value' => $this->configuration['p_confirm_method'],
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $id = $form_state->getValue('id');
    if (substr($id, 0, 7) != 'epayco_') {
      $id = 'epayco_' . $id;
      // We need a way to know which payments are being processed with ePayco.
      $form_state->setValue('id', $id);
    }
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['configuration'] = $values['configuration'];
      $this->configuration['p_confirm_method'] = $values['p_confirm_method'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    /*
     * Let's get the choosen Payment Gateway.
     * This is a "workaround" for $this->entityId == NULL.
     * @see Drupal\commerce_epayco\PluginForm\OffsiteRedirect\PaymentOffsiteForm::buildConfigurationForm.
     * @todo Check for a better way.
     */
    $tempstore = \Drupal::service('user.private_tempstore')->get('commerce_epayco');
    $payment_gateway = isset($this->entityId) ? $this->entityId : $tempstore->get('payment_gateway');

    // Some variables returned by ePayco.
    $__x_cod_response = $request->request->get('x_cod_response');
    $__x_response = $request->request->get('x_response');
    $__x_transaction_id = $request->request->get('x_ref_payco');
    /*
    $__x_response_reason_text = $request->request->get('x_response_reason_text');
     */
    switch ($__x_cod_response) {
      // Aceptada - Accepted.
      case 1:
        // Pendiente - Pending.
      case 3:
        $payment_status = $__x_cod_response == 1 ? 'completed' : 'authorization';
        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
        $payment = $payment_storage->create([
          'state' => $payment_status,
          'amount' => $order->getTotalPrice(),
          'payment_gateway' => $payment_gateway,
          'order_id' => $order->id(),
          'test' => $this->getMode() == 'test',
          'remote_id' => $__x_transaction_id,
          'remote_state' => $__x_response,
          'authorized' => REQUEST_TIME,
        ]);
        $payment->save();
        drupal_set_message($this->t('Transaction @status by ePayco.', ['@status' => $__x_response]));
        break;

      // Rechazada - Rejected.
      case 2:
        // Fallida - Failed.
      case 4:
        drupal_set_message($this->t('Order was finished, but payment transaction failed. ePayco says: @status', ['@status' => $__x_response]), 'error');
        // @todo: Watchdog $__x_response_reason_text.
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    $status = $request->get('status');
    drupal_set_message($this->t('Payment @status on @gateway but may resume the checkout process here when you are ready.', [
      '@status' => $status,
      '@gateway' => $this->getDisplayLabel(),
    ]), 'error');
  }

}
