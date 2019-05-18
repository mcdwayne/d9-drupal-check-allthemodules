<?php

namespace Drupal\cielo\Plugin\BusinessRulesCondition;

use Cielo\API30\Ecommerce\Payment;
use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\cielo\Entity\CieloProfile;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CieloCreditCardPaymentSucceed.
 *
 * @package Drupal\cielo\Plugin\BusinessRulesCondition
 *
 * @BusinessRulesCondition(
 *   id = "cielo_credit_card_payment_succeed",
 *   label = @Translation("Cielo credit card payment succeeds"),
 *   group = @Translation("Cielo"),
 *   description = @Translation("Check if cielo credit card payment has succeeded."),
 *   isContextDependent = FALSE,
 *   reactsOnIds = {},
 *   hasTargetEntity = FALSE,
 *   hasTargetBundle = FALSE,
 *   hasTargetField = FALSE,
 * )
 */
class CieloCreditCardPaymentSucceed extends CieloPaymentSucceed {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $settings['help'] = [
      '#type' => 'item',
      '#markup' => t('More information on <a href="https://developercielo.github.io/manual/cielo-ecommerce#transação-completa" target="_blank">Cielo Api page</a>'),
    ];

    $settings += parent::getSettingsForm($form, $form_state, $item);

    // Unset non credit card information.
    unset($settings['debit_card']);

    // Unset boleto information.
    unset($settings['payment']['return_url']);
    unset($settings['payment']['payment_adress']);
    unset($settings['payment']['payment_boleto_number']);
    unset($settings['payment']['payment_assignor']);
    unset($settings['payment']['payment_demonstrative']);
    unset($settings['payment']['payment_expiration_date']);
    unset($settings['payment']['payment_identification']);
    unset($settings['payment']['payment_instructions']);

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function process(ConditionInterface $condition, BusinessRulesEvent $event) {
    $result = FALSE;

    $cieloSale = parent::ProcessPayment($condition, $event, Payment::PAYMENTTYPE_CREDITCARD);

    if (!$cieloSale) {
      return FALSE;
    }

    $returnCode = $cieloSale->getPayment()->getReturnCode();
    $returnMessage = $cieloSale->getPayment()->getReturnMessage();

    $profile = $condition->getSettings('profile');
    $cielo_profile = CieloProfile::load($profile);

    // 4/6	Operação realizada com sucesso
    // 05	Não Autorizada
    // 57	Cartão Expirado
    // 78	Cartão Bloqueado
    // 99	Time Out
    // 77	Cartão Cancelado
    // 70	Problemas com o Cartão de Crédito
    if ($cielo_profile->getEnvironment() == 'sandbox') {
      $result = in_array($returnCode, [4, 6]);
    }
    else {
      // Production.
      $result = in_array($returnCode, [0]);
    }
    if (!$result) {
      /** @var \Drupal\Core\Messenger\Messenger $messenger */
      $messenger = \Drupal::getContainer()->get('messenger');
      $message = t('It was not possible to process your credit card. Cod: @cod, Message: @message.', [
        '@cod' => $returnCode,
        '@message' => $returnMessage,
      ]);
      $messenger->addMessage($message, 'error');
      \Drupal::keyValueExpirable('cielo')->set('CieloCreditCardPaymentSucceed', $message);
    }

    return $result;
  }

}
