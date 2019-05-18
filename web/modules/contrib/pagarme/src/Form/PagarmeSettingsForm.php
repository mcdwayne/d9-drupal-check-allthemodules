<?php

namespace Drupal\pagarme\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PagarmeSettingsForm.
 *
 * @package Drupal\pagarme\Form
 */
class PagarmeSettingsForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pagarme_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['basic_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configurações básicas'),
    ];
    $form['basic_settings']['pagarme_server'] = [
      '#type' => 'select',
      '#title' => $this->t('Servidor Pagar.me'),
      '#description' => $this->t('TEST - Usar para testar, LIVE - Usar para o processamento de transações reais.'),
      '#options' => array('test' => $this->t('TEST'), 'live' => $this->t('LIVE')),
      '#required' => TRUE,
    ];
    $form['basic_settings']['pagarme_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Chave de API'),
      '#description' => $this->t('Chave da API (disponível no seu dashboard).'),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
    ];
    $form['basic_settings']['pagarme_encryption_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Chave de criptografia'),
      '#description' => $this->t('Chave de encriptação (disponível no seu dashboard).'),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
    ];
    $form['basic_settings']['pagarme_display_title_pay_button'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Título exibido no botão de pagamento'),
      '#description' => $this->t('Texto usado no botão de pagamento (página de pagamento).'),
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $form['basic_settings']['pagarme_ui_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cor primária da interface de Checkout'),
      '#description' => $this->t('Cor primária da interface de Checkout.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
    ];
    $form['basic_settings']['pagarme_debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Habilitar depuração de cada ação deste módulo'),
    ];
    $form['advanced_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configurações avançadas'),
    ];
    $form['advanced_settings']['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configurações gerais'),
    ];
    $general_settings = &$form['advanced_settings']['general'];
    $general_settings['pagarme_checkout_complete_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL de redirecionamento ao concluir a transação'),
      '#description' => $this->t('URL de redirecionamento ao concluir a transação (checkout completo).'),
      '#maxlength' => 255,
      '#size' => 255,
    ];
    $general_settings['pagarme_checkout_failure_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL de redirecionamento caso a transação não possa ser processada pela Pagar.me'),
      '#description' => $this->t('URL de redirecionamento caso a transação não possa ser processada pela Pagar.me.'),
      '#maxlength' => 255,
      '#size' => 255,
    ];
    $form['advanced_settings']['order_display'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configurações de pedido'),
    ];
    $order_display_settings = &$form['advanced_settings']['order_display'];
    $order_display_settings['pagarme_street_number_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token do número do imóvel'),
      '#description' => $this->t('De onde você deseja obter os dados do campo &quot;número do imóvel&quot; para enviar para Pagar.me. &lt;strong&gt;Você pode usar tokens&lt;/strong&gt; aqui(veja o navegador de tokens abaixo).'),
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $order_display_settings['pagarme_phone_ddd_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token do DDD do telefone'),
      '#description' => $this->t('De onde você deseja obter os dados do campo &quot;DDD do telefone&quot; para enviar para Pagar.me. &lt;strong&gt;Você pode usar tokens&lt;/strong&gt; aqui(veja o navegador de tokens abaixo).'),
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $order_display_settings['pagarme_phone_number_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token do número de telefone'),
      '#description' => $this->t('De onde você deseja obter os dados do campo &quot;Telefone&quot; para enviar para Pagar.me. &lt;strong&gt;Você pode usar tokens&lt;/strong&gt; aqui(veja o navegador de tokens abaixo).'),
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $order_display_settings['pagarme_cpf_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token de CPF'),
      '#description' => $this->t('De onde você deseja obter os dados de campo &quot;CPF&quot; para enviar para Pagar.me. &lt;strong&gt;Você pode usar tokens&lt;/strong&gt; aqui(veja o navegador de tokens abaixo).'),
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $order_display_settings['pagarme_cnpj_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token de CNPJ'),
      '#description' => $this->t('De onde você deseja obter os dados de campo &quot;CNPJ&quot; para enviar para Pagar.me. &lt;strong&gt;Você pode usar tokens&lt;/strong&gt; aqui(veja o navegador de tokens abaixo).'),
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $order_display_settings['pagarme_birthday_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token de data de nascimento'),
      '#description' => $this->t('De onde você deseja obter os dados do campo &quot;Data de nascimento&quot; para enviar para Pagar.me. &lt;strong&gt;Você pode usar tokens&lt;/strong&gt; aqui(veja o navegador de tokens abaixo).'),
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $order_display_settings['pagarme_gender_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token de gênero(sexo)'),
      '#description' => $this->t('De onde você deseja obter os dados do campo &quot;Gênero(sexo)&quot; para enviar para Pagar.me. &lt;strong&gt;Você pode usar tokens&lt;/strong&gt; aqui(veja o navegador de tokens abaixo).'),
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $form['advanced_settings']['payment_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configurações de pagamento'),
    ];
    $payment_settings = &$form['advanced_settings']['payment_settings'];
    $payment_settings['pagarme_customer_data'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Capturar dados do cliente pelo Checkout ?'),
    ];
    $payment_settings['pagarme_disable_zero_document_number'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Não aceitar CPF ou CNPJ em que todos os números são zeros ?'),
    ];
    $payment_settings['pagarme_payment_methods'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Meios de pagamento disponíveis no Checkout'),
      '#description' => $this->t('Meios de pagamento disponíveis no Checkout.'),
      '#options' => array('credit_card' => $this->t('Cartão de crédito'), 'boleto' => $this->t('Boleto')),
    ];
    $payment_settings['pagarme_max_installments'] = [
      '#type' => 'select',
      '#title' => $this->t('Número máximo de parcelas'),
      '#description' => $this->t('Número máximo de parcelas aceitas.'),
      '#options' => \Drupal\pagarme\Helpers\PagarmeUtility::installmentsNumber(),
    ];
    $payment_settings['pagarme_default_installment'] = [
      '#type' => 'select',
      '#title' => $this->t('Parcela padrão selecionada ao abrir o checkout'),
      '#description' => $this->t('Parcela padrão selecionada ao abrir o checkout.'),
      '#options' => array('1' => $this->t('1'), 'boleto' => $this->t('boleto')),
      '#options' => \Drupal\pagarme\Helpers\PagarmeUtility::installmentsNumber(),
    ];
    $payment_settings['pagarme_interest_rate'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Taxa de juros a ser cobrada na transação'),
      '#description' => $this->t('Taxa de juros a ser cobrada na transação.'),
      '#maxlength' => 15,
      '#size' => 15,
    ];
    $payment_settings['pagarme_installment_start_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Liberar parcelamento acima de'),
      '#description' => $this->t('Libera o parcelamento para pedidos com valor acima do valor informado.'),
      '#maxlength' => 15,
      '#size' => 15,
    ];
    $payment_settings['pagarme_free_installments'] = [
      '#type' => 'select',
      '#title' => $this->t('Número de parcelas que não terão juros cobrados'),
      '#description' => $this->t('Número de parcelas que não terão juros cobrados.'),
      '#options' => \Drupal\pagarme\Helpers\PagarmeUtility::installmentsNumber(),
    ];
    $payment_settings['pagarme_card_brands'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Bandeiras aceitas pelo Checkout'),
      '#description' => $this->t('Bandeiras aceitas pelo Checkout.'),
      '#options' => \Drupal\pagarme\Helpers\PagarmeUtility::cardBrands(),
    ];
    $payment_settings['pagarme_boleto_discount'] = [
      '#type' => 'select',
      '#title' => $this->t('Desconto boleto'),
      '#description' => $this->t('Selecione o tipo de desconto para boleto.'),
      '#options' => array('_none' => $this->t('None'), 'amount' => $this->t('Desconto em centavos'), 'percentage' => $this->t('Percentual de desconto')),
    ];
    $payment_settings['pagarme_boleto_discount_amount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Valor do desconto'),
      '#description' => $this->t('Valor do desconto caso o meio de pagamento seja boleto. Ex: desconto de R$10,25 = 10.25.'),
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $payment_settings['pagarme_boleto_discount_percentage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Percentual de desconto'),
      '#description' => $this->t('Percentual de desconto caso o meio de pagamento seja boleto. Ex: desconto de 25% = 25.'),
      '#maxlength' => 2,
      '#size' => 4,
    ];
    $payment_settings['pagarme_boleto_discount_start'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Aplicar desconto a partir de'),
      '#description' => $this->t('Irá aplicar desconto apenas em pedidos no qual o valor sejá maior que o valor informado. Ex: Aplicar desconto a partir de R$999,25 = 999.25.'),
      '#maxlength' => 64,
      '#size' => 64,
    ];
    $payment_settings['pagarme_boleto_helper_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mensagem opcional boleto'),
      '#description' => $this->t('Mensagem opcional que aparecerá embaixo do botão de pagamento Boleto.'),
    ];
    $payment_settings['pagarme_credit_card_helper_text'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Mensagem opcional cartão de crédito'),
      '#description' => $this->t('Mensagem opcional que aparecerá embaixo do botão de pagamento cartão de crédito.'),
    ];

    $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save settings'),
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
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
        drupal_set_message($key . ': ' . $value);
    }

  }

}
