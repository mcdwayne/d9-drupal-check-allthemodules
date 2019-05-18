<?php

namespace Drupal\cielo\Plugin\BusinessRulesCondition;

use Cielo\API30\Ecommerce\Payment;
use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CieloBoletoPaymentSucceed.
 *
 * @package Drupal\cielo\Plugin\BusinessRulesCondition
 *
 * @BusinessRulesCondition(
 *   id = "cielo_boleto_payment_succeed",
 *   label = @Translation("Cielo boleto payment succeeds"),
 *   group = @Translation("Cielo"),
 *   description = @Translation("Check if cielo boleto payment has succeeded."),
 *   isContextDependent = FALSE,
 *   reactsOnIds = {},
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class CieloBoletoPaymentSucceed extends CieloPaymentSucceed {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $settings['help'] = [
      '#type' => 'item',
      '#markup' => t('More information on <a href="https://developercielo.github.io/manual/cielo-ecommerce#transação-de-boletos" target="_blank">Cielo Api page</a>'),
    ];

    $settings += parent::getSettingsForm($form, $form_state, $item);

    unset($settings['credit_card']);
    unset($settings['debit_card']);

    return $settings;
  }
  /**
   * {@inheritdoc}
   */
  public function process(ConditionInterface $condition, BusinessRulesEvent $event) {
    $result = TRUE;

    $cieloSale = parent::ProcessPayment($condition, $event, Payment::PAYMENTTYPE_BOLETO);

    if (!$cieloSale) {
      return FALSE;
    }

    $boletoBarCode = $cieloSale->getPayment()->getBarCodeNumber();

    if (!$boletoBarCode) {
      drupal_set_message(t('It was not possible to process the Boleto.'), 'error');
      $result = FALSE;
    }

    return $result;
  }

}
