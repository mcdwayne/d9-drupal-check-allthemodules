<?php

namespace Drupal\cielo\Plugin\BusinessRulesCondition;

use Drupal\business_rules\ConditionInterface;
use Drupal\business_rules\Events\BusinessRulesEvent;
use Drupal\business_rules\ItemInterface;
use Drupal\business_rules\Plugin\BusinessRulesConditionPlugin;
use Drupal\cielo\Entity\CieloPayment;
use Drupal\cielo\Entity\CieloProfile;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ProcessCieloPayment.
 *
 * @package Drupal\cielo\Plugin
 */
abstract class CieloPaymentSucceed extends BusinessRulesConditionPlugin {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(array &$form, FormStateInterface $form_state, ItemInterface $item) {

    $settings['profile'] = [
      '#type' => 'textfield',
      '#title' => t('Profile'),
      '#description' => t('Enter the machine name of the cielo profile for this transaction.'),
      '#default_value' => $item->getSettings('profile'),
      '#required' => TRUE,
    ];

    // As business rules only store the first level of $settings, we need to
    // include placeholders for this information.
    $settings['credit_card_holder']          = [];
    $settings['credit_card_card_number']     = [];
    $settings['credit_card_security_code']   = [];
    $settings['credit_card_expiration_date'] = [];
    $settings['credit_card_brand']           = [];

    // Credit card information.
    $settings['credit_card'] = [
      '#type'        => 'fieldset',
      '#title'       => t('Credit Card Information'),
      '#description' => t('This information are trunked on the database according to the response of cielo API and you should not store the raw information in any case.'),
    ];

    $settings['credit_card']['credit_card_holder'] = [
      '#type'          => 'textfield',
      '#title'         => t('Credit Card Holder'),
      '#description'   => t('Name impressed on the card. You should use token or variable value.'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('credit_card_holder'),
    ];

    $settings['credit_card']['credit_card_card_number'] = [
      '#type'          => 'textfield',
      '#title'         => t('Credit Card Number'),
      '#description'   => t('The credit card number. You should use token or variable value.'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('credit_card_card_number'),
    ];

    $settings['credit_card']['credit_card_security_code'] = [
      '#type'          => 'textfield',
      '#title'         => t('Credit Card Security Code'),
      '#description'   => t('The credit security code. You should use token or variable value.'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('credit_card_security_code'),
    ];

    $settings['credit_card']['credit_card_expiration_date'] = [
      '#type'          => 'textfield',
      '#title'         => t('Credit Card Expiration Date'),
      '#description'   => t('Format: mm/yyyy. You should use token or variable value.'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('credit_card_expiration_date'),
    ];

    $settings['credit_card']['credit_card_brand'] = [
      '#type'          => 'textfield',
      '#title'         => t('Credit Card Brand'),
      '#description'   => t('Valid names: Visa / Master / Amex / Elo / Aura / JCB / Diners / Discover / Hipercard. You may use token or variable value.'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('credit_card_brand'),
    ];

    // As business rules only store the first level of $settings, we need to
    // include placeholders for this information.
    $settings['debit_card_card_number']     = [];
    $settings['debit_card_holder']          = [];
    $settings['debit_card_expiration_date'] = [];
    $settings['debit_card_security_code']   = [];
    $settings['debit_card_brand']           = [];

    // Debit card information.
    $settings['debit_card'] = [
      '#type'        => 'fieldset',
      '#title'       => t('Debit Card Information'),
      '#description' => t('This information are trunked on the database according to the response of cielo API and you should not store the raw information in any case.'),
    ];

    $settings['debit_card']['debit_card_card_number'] = [
      '#type'          => 'textfield',
      '#title'         => t('DebitCard.CardNumber'),
      '#description'   => t('Número do Cartão do Comprador.'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('debit_card_card_number'),
    ];

    $settings['debit_card']['debit_card_holder'] = [
      '#type'          => 'textfield',
      '#title'         => t('DebitCard.Holder'),
      '#description'   => t('Nome do Comprador impresso no cartão.'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('debit_card_holder'),
    ];

    $settings['debit_card']['debit_card_expiration_date'] = [
      '#type'          => 'textfield',
      '#title'         => t('DebitCard.ExpirationDate'),
      '#description'   => t('Data de validade impresso no cartão.'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('debit_card_expiration_date'),
    ];

    $settings['debit_card']['debit_card_security_code'] = [
      '#type'          => 'textfield',
      '#title'         => t('DebitCard.SecurityCode'),
      '#description'   => t('Código de segurança impresso no verso do cartão.'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('debit_card_security_code'),
    ];

    $settings['debit_card']['debit_card_brand'] = [
      '#type'          => 'textfield',
      '#title'         => t('DebitCard.SecurityCode'),
      '#description'   => t('Código de segurança impresso no verso do cartão.'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('Bandeira do cartão.'),
    ];

    // As business rules only store the first level of $settings, we need to
    // include placeholders for this information.
    $settings['merchant_order_id']          = [];
    $settings['payment_amount']             = [];
    $settings['payment_currency']           = [];
    $settings['payment_country']            = [];
    $settings['payment_service_tax_amount'] = [];
    $settings['payment_provider']           = [];
    $settings['payment_soft_descriptor']    = [];
    $settings['payment_installments']       = [];
    $settings['payment_interest']           = [];
    $settings['payment_capture']            = [];
    $settings['payment_authenticate']       = [];
    $settings['return_url']                 = [];
    $settings['payment_adress']             = [];
    $settings['payment_boleto_number']      = [];
    $settings['payment_assignor']           = [];
    $settings['payment_demonstrative']      = [];
    $settings['payment_expiration_date']    = [];
    $settings['payment_identification']     = [];
    $settings['payment_instructions']       = [];

    // Payment information.
    $settings['payment'] = [
      '#type'        => 'fieldset',
      '#title'       => t('Payment Information'),
      '#description' => t('This information is stored on database by cielo module.'),
    ];

    $settings['payment']['merchant_order_id'] = [
      '#type'          => 'textfield',
      '#title'         => t('Merchant Order Id'),
      '#description'   => t('Order identification number. You may use token or variable value.'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('merchant_order_id'),
    ];

    $settings['payment']['payment_amount'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment Amount'),
      '#description'   => t('The Payment Amount information. It must be an integer or a number with two decimals. You may use token or variable value.'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('payment_amount'),
    ];

    $settings['payment']['payment_currency'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment Currency'),
      '#description'   => t('Moeda na qual o pagamento será feito (BRL). If not informed, BLR will be used. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('payment_currency'),
    ];

    $settings['payment']['payment_country'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment Country'),
      '#description'   => t('Pais na qual o pagamento será feito. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('payment_country'),
    ];

    $settings['payment']['payment_service_tax_amount'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment Service Tax Amount'),
      '#description'   => t('Payment Service Tax Amount. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('payment_service_tax_amount'),
    ];

    $settings['payment']['payment_provider'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment Provider'),
      '#description'   => t('Define comportamento do meio de pagamento (ver Anexo)/NÃO OBRIGATÓRIO PARA CRÉDITO.	. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('payment_provider'),
    ];

    $settings['payment']['payment_soft_descriptor'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment Soft Descriptor'),
      '#description'   => t('Texto impresso na fatura bancaria comprador - Exclusivo para VISA/MASTER - não permite caracteres especiais máximo 13 caracteres. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('payment_soft_descriptor'),
    ];

    $settings['payment']['payment_installments'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment Installments'),
      '#description'   => t('Número de Parcelas. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('payment_installments'),
    ];

    $settings['payment']['payment_interest'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment Interest'),
      '#description'   => t('Tipo de parcelamento - Loja (ByMerchant) ou Cartão (ByIssuer). You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('payment_interest'),
    ];

    $settings['payment']['payment_capture'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment Capture'),
      '#description'   => t('Booleano que identifica que a autorização deve ser com captura automática. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('payment_capture'),
    ];

    $settings['payment']['payment_authenticate'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment Authenticate'),
      '#description'   => t('Booleano que define se o comprador será direcionado ao Banco emissor para autenticação do cartão. Cartões de Débito, por padrão, devem possuir `Authenticate` como TRUE. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('payment_authenticate'),
    ];

    $settings['payment']['return_url'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment.ReturnUrl'),
      '#description'   => t('URI para onde o usuário será redirecionado após o fim do pagamento. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('return_url'),
    ];

    $settings['payment']['payment_adress'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment.Adress'),
      '#description'   => t('Endereço do Cedente. (typo originated on the CIELO API). You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('payment_adress'),
    ];

    $settings['payment']['payment_boleto_number'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment.BoletoNumber'),
      '#description'   => t('Número do Boleto enviado pelo lojista. Usado para contar boletos emitidos (“NossoNumero”). You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('payment_boleto_number'),
    ];

    $settings['payment']['payment_assignor'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment.Assignor'),
      '#description'   => t('Nome do Cedente. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('payment_assignor'),
    ];

    $settings['payment']['payment_demonstrative'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment.Demonstrative'),
      '#description'   => t('Texto de Demonstrativo. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('payment_demonstrative'),
    ];

    $settings['payment']['payment_expiration_date'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment.ExpirationDate'),
      '#description'   => t('Data de expiração do Boleto. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('payment_expiration_date'),
    ];

    $settings['payment']['payment_identification'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment.Identification'),
      '#description'   => t('Documento de identificação do Cedente. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('payment_identification'),
    ];

    $settings['payment']['payment_instructions'] = [
      '#type'          => 'textfield',
      '#title'         => t('Payment.Instructions'),
      '#description'   => t('Instruções do Boleto. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('payment_instructions'),
    ];

    // As business rules only store the first level of $settings, we need to
    // include placeholders for this information.
    // TODO check this fields again.
    $settings['customer_name']                        = [];
    $settings['customer_status']                      = [];
    $settings['customer_identity']                    = [];
    $settings['customer_identity_type']               = [];
    $settings['customer_email']                       = [];
    $settings['customer_birthdate']                   = [];
    $settings['customer_address_street']              = [];
    $settings['customer_address_number']              = [];
    $settings['customer_address_complement']          = [];
    $settings['customer_address_district']            = [];
    $settings['customer_address_zip_code']            = [];
    $settings['customer_address_city']                = [];
    $settings['customer_address_state']               = [];
    $settings['customer_address_country']             = [];
    $settings['customer_delivery_address_street']     = [];
    $settings['customer_delivery_address_number']     = [];
    $settings['customer_delivery_address_complement'] = [];
    $settings['customer_delivery_address_zip_code']   = [];
    $settings['customer_delivery_address_city']       = [];
    $settings['customer_delivery_address_state']      = [];
    $settings['customer_delivery_address_country']    = [];

    $settings['customer'] = [
      '#type'        => 'fieldset',
      '#title'       => t('Customer Information'),
      '#description' => t('This information is stored on database by cielo module.'),
    ];

    $settings['customer']['customer_name'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer name'),
      '#description'   => t('The customer name. You may use token or variable value.'),
      '#required'      => TRUE,
      '#default_value' => $item->getSettings('customer_name'),
    ];

    $settings['customer']['customer_status'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer Status'),
      '#description'   => t('Status de cadastro do comprador na loja (NEW / EXISTING). You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_status'),
    ];

    $settings['customer']['customer_identity'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer Identity'),
      '#description'   => t('Número do RG, CPF ou CNPJ do Cliente. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_identity'),
    ];

    $settings['customer']['customer_identity_type'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer IdentityType'),
      '#description'   => t('Tipo de documento de identificação do comprador (CFP/CNPJ). You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_identity_type'),
    ];

    $settings['customer']['customer_email'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer Email'),
      '#description'   => t('Email do Comprador. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_email'),
    ];

    $settings['customer']['customer_birthdate'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer Birthdate'),
      '#description'   => t('Data de nascimento do Comprador. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_birthdate'),
    ];

    $settings['customer']['customer_address_street'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer Address Street'),
      '#description'   => t('Endereço do Comprador. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_address_street'),
    ];

    $settings['customer']['customer_address_number'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer.Address.Number'),
      '#description'   => t('Número do endereço do Comprador. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_address_number'),
    ];

    $settings['customer']['customer_address_complement'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer Address Complement'),
      '#description'   => t('Complemento do endereço do Comprador. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_address_complement'),
    ];

    $settings['customer']['customer_address_district'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer.Address.District'),
      '#description'   => t('Bairro do Comprador.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_address_district'),
    ];

    $settings['customer']['customer_address_zip_code'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer Address ZipCode'),
      '#description'   => t('CEP do endereço do Comprador. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_address_zip_code'),
    ];

    $settings['customer']['customer_address_city'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer Address City'),
      '#description'   => t('. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_address_city'),
    ];

    $settings['customer']['customer_address_state'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer Address State'),
      '#description'   => t('Estado do endereço do Comprador. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_address_state'),
    ];

    $settings['customer']['customer_address_country'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer Address Country'),
      '#description'   => t('Pais do endereço do Comprador. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_address_country'),
    ];

    $settings['customer']['customer_delivery_address_street'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer DeliveryAddress Street'),
      '#description'   => t('Endereço de entrega do Comprador. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_delivery_address_street'),
    ];

    $settings['customer']['customer_delivery_address_number'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer Delivery Address Number'),
      '#description'   => t('Número do endereço de entrega do Comprador. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_delivery_address_number'),
    ];

    $settings['customer']['customer_delivery_address_complement'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer Delivery Address Complement'),
      '#description'   => t('Complemento do endereço de entrega do Comprador. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_delivery_address_complement'),
    ];

    $settings['customer']['customer_delivery_address_zip_code'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer Delivery Address ZipCode'),
      '#description'   => t('CEP do endereço de entrega do Comprador. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_delivery_address_zip_code'),
    ];

    $settings['customer']['customer_delivery_address_city'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer Delivery Address City'),
      '#description'   => t('Cidade do endereço de entrega do Comprador. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_delivery_address_city'),
    ];

    $settings['customer']['customer_delivery_address_state'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer Delivery Address State'),
      '#description'   => t('Estado do endereço de entrega do Comprador. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_delivery_address_state'),
    ];

    $settings['customer']['customer_delivery_address_country'] = [
      '#type'          => 'textfield',
      '#title'         => t('Customer Delivery Address Country'),
      '#description'   => t('Pais do endereço de entrega do Comprador. You may use token or variable value.'),
      '#required'      => FALSE,
      '#default_value' => $item->getSettings('customer_delivery_address_country'),
    ];

    return $settings;
  }

  /**
   * Process cielo payment.
   *
   * @param \Drupal\business_rules\ConditionInterface $condition
   * @param \Drupal\business_rules\Events\BusinessRulesEvent $event
   * @param $payment_type
   *
   * @return \Cielo\API30\Ecommerce\Sale
   */
  public function ProcessPayment(ConditionInterface $condition, BusinessRulesEvent $event, $payment_type) {
    $variables = $event->getArgument('variables');

    $profile = $condition->getSettings('profile');

    $merchant_order_id = $condition->getSettings('merchant_order_id');

    // Credit card info.
    $credit_card_holder          = $condition->getSettings('credit_card_holder');
    $credit_card_card_number     = $condition->getSettings('credit_card_card_number');
    $credit_card_security_code   = $condition->getSettings('credit_card_security_code');
    $credit_card_expiration_date = $condition->getSettings('credit_card_expiration_date');
    $credit_card_brand           = $condition->getSettings('credit_card_brand');

    // Debit card info.
    $debit_card_card_number     = $condition->getSettings('debit_card_card_number');
    $debit_card_holder          = $condition->getSettings('debit_card_holder');
    $debit_card_expiration_date = $condition->getSettings('debit_card_expiration_date');
    $debit_card_security_code   = $condition->getSettings('debit_card_security_code');
    $debit_card_brand           = $condition->getSettings('debit_card_brand');

    // Payment info.
    $payment_amount             = $condition->getSettings('payment_amount');
    $payment_currency           = $condition->getSettings('payment_currency');
    $payment_country            = $condition->getSettings('payment_country');
    $payment_service_tax_amount = $condition->getSettings('payment_service_tax_amount');
    $payment_provider           = $condition->getSettings('payment_provider');
    $payment_soft_descriptor    = $condition->getSettings('payment_soft_descriptor');
    $payment_installments       = $condition->getSettings('payment_installments');
    $payment_interest           = $condition->getSettings('payment_interest');
    $payment_capture            = $condition->getSettings('payment_capture');
    $payment_authenticate       = $condition->getSettings('payment_authenticate');
    $return_url                 = $condition->getSettings('return_url');
    $payment_adress             = $condition->getSettings('payment_adress');
    $payment_boleto_number      = $condition->getSettings('payment_boleto_number');
    $payment_assignor           = $condition->getSettings('payment_assignor');
    $payment_demonstrative      = $condition->getSettings('payment_demonstrative');
    $payment_expiration_date    = $condition->getSettings('payment_expiration_date');
    $payment_identification     = $condition->getSettings('payment_identification');
    $payment_instructions       = $condition->getSettings('payment_instructions');

    // Customer info.
    $customer_name                        = $condition->getSettings('customer_name');
    $customer_status                      = $condition->getSettings('customer_status');
    $customer_identity                    = $condition->getSettings('customer_identity');
    $customer_identity_type               = $condition->getSettings('customer_identity_type');
    $customer_email                       = $condition->getSettings('customer_email');
    $customer_birthdate                   = $condition->getSettings('customer_birthdate');
    $customer_address_street              = $condition->getSettings('customer_address_street');
    $customer_address_number              = $condition->getSettings('customer_address_number');
    $customer_address_complement          = $condition->getSettings('customer_address_complement');
    $customer_address_district            = $condition->getSettings('customer_address_district');
    $customer_address_zip_code            = $condition->getSettings('customer_address_zip_code');
    $customer_address_city                = $condition->getSettings('customer_address_city');
    $customer_address_state               = $condition->getSettings('customer_address_state');
    $customer_address_country             = $condition->getSettings('customer_address_country');
    $customer_delivery_address_street     = $condition->getSettings('customer_delivery_address_street');
    $customer_delivery_address_number     = $condition->getSettings('customer_delivery_address_number');
    $customer_delivery_address_complement = $condition->getSettings('customer_delivery_address_complement');
    $customer_delivery_address_zip_code   = $condition->getSettings('customer_delivery_address_zip_code');
    $customer_delivery_address_city       = $condition->getSettings('customer_delivery_address_city');
    $customer_delivery_address_state      = $condition->getSettings('customer_delivery_address_state');
    $customer_delivery_address_country    = $condition->getSettings('customer_delivery_address_country');

    // Process variables.
    $profile = $this->processVariables($profile, $variables);

    $credit_card_holder          = $this->processVariables($credit_card_holder, $variables);
    $credit_card_card_number     = $this->processVariables($credit_card_card_number, $variables);
    $credit_card_security_code   = $this->processVariables($credit_card_security_code, $variables);
    $credit_card_expiration_date = $this->processVariables($credit_card_expiration_date, $variables);
    $credit_card_brand           = $this->processVariables($credit_card_brand, $variables);

    $debit_card_card_number     = $this->processVariables($debit_card_card_number, $variables);
    $debit_card_holder          = $this->processVariables($debit_card_holder, $variables);
    $debit_card_expiration_date = $this->processVariables($debit_card_expiration_date, $variables);
    $debit_card_security_code   = $this->processVariables($debit_card_security_code, $variables);
    $debit_card_brand           = $this->processVariables($debit_card_brand, $variables);

    $merchant_order_id          = $this->processVariables($merchant_order_id, $variables);
    $payment_amount             = $this->processVariables($payment_amount, $variables);
    $payment_currency           = $this->processVariables($payment_currency, $variables);
    $payment_country            = $this->processVariables($payment_country, $variables);
    $payment_service_tax_amount = $this->processVariables($payment_service_tax_amount, $variables);
    $payment_provider           = $this->processVariables($payment_provider, $variables);
    $payment_soft_descriptor    = $this->processVariables($payment_soft_descriptor, $variables);
    $payment_installments       = $this->processVariables($payment_installments, $variables);
    $payment_interest           = $this->processVariables($payment_interest, $variables);
    $payment_capture            = $this->processVariables($payment_capture, $variables);
    $payment_authenticate       = $this->processVariables($payment_authenticate, $variables);
    $return_url                 = $this->processVariables($return_url, $variables);
    $payment_adress             = $this->processVariables($payment_adress, $variables);
    $payment_boleto_number      = $this->processVariables($payment_boleto_number, $variables);
    $payment_assignor           = $this->processVariables($payment_assignor, $variables);
    $payment_demonstrative      = $this->processVariables($payment_demonstrative, $variables);
    $payment_expiration_date    = $this->processVariables($payment_expiration_date, $variables);
    $payment_identification     = $this->processVariables($payment_identification, $variables);
    $payment_instructions       = $this->processVariables($payment_instructions, $variables);

    $customer_name                        = $this->processVariables($customer_name, $variables);
    $customer_status                      = $this->processVariables($customer_status, $variables);
    $customer_identity                    = $this->processVariables($customer_identity, $variables);
    $customer_identity_type               = $this->processVariables($customer_identity_type, $variables);
    $customer_email                       = $this->processVariables($customer_email, $variables);
    $customer_birthdate                   = $this->processVariables($customer_birthdate, $variables);
    $customer_address_street              = $this->processVariables($customer_address_street, $variables);
    $customer_address_number              = $this->processVariables($customer_address_number, $variables);
    $customer_address_complement          = $this->processVariables($customer_address_complement, $variables);
    $customer_address_district            = $this->processVariables($customer_address_district, $variables);
    $customer_address_zip_code            = $this->processVariables($customer_address_zip_code, $variables);
    $customer_address_city                = $this->processVariables($customer_address_city, $variables);
    $customer_address_state               = $this->processVariables($customer_address_state, $variables);
    $customer_address_country             = $this->processVariables($customer_address_country, $variables);
    $customer_delivery_address_street     = $this->processVariables($customer_delivery_address_street, $variables);
    $customer_delivery_address_number     = $this->processVariables($customer_delivery_address_number, $variables);
    $customer_delivery_address_complement = $this->processVariables($customer_delivery_address_complement, $variables);
    $customer_delivery_address_zip_code   = $this->processVariables($customer_delivery_address_zip_code, $variables);
    $customer_delivery_address_city       = $this->processVariables($customer_delivery_address_city, $variables);
    $customer_delivery_address_state      = $this->processVariables($customer_delivery_address_state, $variables);
    $customer_delivery_address_country    = $this->processVariables($customer_delivery_address_country, $variables);

    $cieloPayment = new CieloPayment([], 'cielo_payment');
    $cieloPayment->set('merchant_order_id', $merchant_order_id);

    $cieloPayment->set('credit_card_holder', $credit_card_holder);
    $cieloPayment->set('credit_card_card_number', $credit_card_card_number);
    $cieloPayment->set('credit_card_security_code', $credit_card_security_code);
    $cieloPayment->set('credit_card_expiration_date', $credit_card_expiration_date);
    $cieloPayment->set('credit_card_brand', $credit_card_brand);

    $cieloPayment->set('debit_card_card_number', $debit_card_card_number);
    $cieloPayment->set('debit_card_holder', $debit_card_holder);
    $cieloPayment->set('debit_card_expiration_date', $debit_card_expiration_date);
    $cieloPayment->set('debit_card_security_code', $debit_card_security_code);
    $cieloPayment->set('debit_card_brand', $debit_card_brand);

    $payment_amount = number_format($payment_amount, 2, '.', '');
    $payment_amount = str_replace('.', '', $payment_amount);
    $cieloPayment->set('payment_amount', $payment_amount);
    $cieloPayment->set('payment_currency', $payment_currency);
    $cieloPayment->set('payment_country', $payment_country);
    $cieloPayment->set('payment_service_tax_amount', $payment_service_tax_amount);
    $cieloPayment->set('payment_provider', $payment_provider);
    $cieloPayment->set('payment_soft_descriptor', $payment_soft_descriptor);
    $cieloPayment->set('payment_installments', $payment_installments);
    $cieloPayment->set('payment_interest', $payment_interest);
    $cieloPayment->set('payment_capture', $payment_capture);
    $cieloPayment->set('payment_authenticate', $payment_authenticate);
    $cieloPayment->set('return_url', $return_url);
    $cieloPayment->set('payment_adress', $payment_adress);
    $cieloPayment->set('payment_boleto_number', $payment_boleto_number);
    $cieloPayment->set('payment_assignor', $payment_assignor);
    $cieloPayment->set('payment_demonstrative', $payment_demonstrative);
    $cieloPayment->set('payment_expiration_date', $payment_expiration_date);
    $cieloPayment->set('payment_identification', $payment_identification);
    $cieloPayment->set('payment_instructions', $payment_instructions);

    $cieloPayment->set('customer_name', $customer_name);
    $cieloPayment->set('customer_status', $customer_status);
    $cieloPayment->set('customer_identity', str_replace('-', '', str_replace('/', '', str_replace('.', '', $customer_identity))));
    $cieloPayment->set('customer_identity_type', $customer_identity_type);
    $cieloPayment->set('customer_email', $customer_email);
    $cieloPayment->set('customer_birthdate', $customer_birthdate);
    $cieloPayment->set('customer_address_street', $customer_address_street);
    $cieloPayment->set('customer_address_number', $customer_address_number);
    $cieloPayment->set('customer_address_complement', $customer_address_complement);
    $cieloPayment->set('customer_address_district', $customer_address_district);
    $cieloPayment->set('customer_address_zip_code', $customer_address_zip_code);
    $cieloPayment->set('customer_address_city', $customer_address_city);
    $cieloPayment->set('customer_address_state', $customer_address_state);
    $cieloPayment->set('customer_address_country', $customer_address_country);
    $cieloPayment->set('customer_delivery_address_street', $customer_delivery_address_street);
    $cieloPayment->set('customer_delivery_address_number', $customer_delivery_address_number);
    $cieloPayment->set('customer_delivery_address_complement', $customer_delivery_address_complement);
    $cieloPayment->set('customer_delivery_address_zip_code', $customer_delivery_address_zip_code);
    $cieloPayment->set('customer_delivery_address_city', $customer_delivery_address_city);
    $cieloPayment->set('customer_delivery_address_state', $customer_delivery_address_state);
    $cieloPayment->set('customer_delivery_address_country', $customer_delivery_address_country);

    $cielo_profile = CieloProfile::load($profile);

    if ($payment_amount) {
      return $cieloPayment->processPayment($payment_type, $cielo_profile);
    }
    else {
      return FALSE;
    }
  }
}
