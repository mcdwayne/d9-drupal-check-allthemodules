<?php

namespace Drupal\pagarme\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\pagarme\Pagarme\PagarmeDrupal;
use Drupal\pagarme\Helpers\PagarmeUtility;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class PagarmePaymentDetail.
 *
 * @package Drupal\pagarme\Form
 */
class PagarmePaymentDetail extends FormBase {

  /**
   * The current order.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $order;

  /**
   * Constructs a new PagarmePaymentDetail object.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match.
   */
  public function __construct(CurrentRouteMatch $current_route_match) {
    $this->order = $current_route_match->getParameter('commerce_order');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('current_route_match'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pagarme_payment_detail_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $payment_gateway */
    $payment_gateway = $this->order->get('payment_gateway');
    $payment_gateway  = current($payment_gateway->referencedEntities());

    $pagarme_payment_gateway = array('pagarme_modal', 'pagarme_billet', 'pagarme_credit_card');
    $plugin_id = $payment_gateway->get('plugin');

    if (in_array($plugin_id, $pagarme_payment_gateway) && $this->order->getData('pagarme_payment_transaction_id')) {
      $plugin_config = $payment_gateway->get('configuration');

      $pagarme_api_key = $plugin_config['pagarme_api_key'];
      $pagarmeDrupal = new PagarmeDrupal($pagarme_api_key);

      try {
        $transaction = $pagarmeDrupal->pagarme->transaction()->get($this->order->getData('pagarme_payment_transaction_id'));
        $payment_method = ($transaction->getPaymentMethod() == 'credit_card') ? $this->t('cartão de crédito') : $this->t('boleto');

        $consumer = $transaction->getCustomer();

        $phone = $transaction->getPhone();
        $inline_phone = '+' . $phone->ddi . ' ' . $phone->ddd . ' ' . $phone->number;

        $address = $transaction->getAddress();
        $inline_address = $address->street . ', ' . $address->street_number;
        $inline_address .= ' ' . $address->complementary;
        $inline_address .= ' ' . $address->neighborhood;
        $inline_address .= ' ' . $address->city;
        $inline_address .= ' ' . $address->state;
        $inline_address .= ' ' . $address->zipcode;
        $inline_address .= ' ' . $address->country;

        $status_readable_name = PagarmeUtility::statusReadableName();

        $rows = array();
        $transaction_id = $transaction->getId();
        $rows[] = array($this->t('ID da transação'), $transaction_id);
        $boleto_created_date = format_date($transaction->getDateCreated()->getTimestamp(), 'short');

        $rows[] = array($this->t('Efetuada em'), $boleto_created_date);
        $rows[] = array($this->t('Método de pagamento utilizado'), $payment_method);

        $currency_code = $this->order->getTotalPrice()->getCurrencyCode();

        $order_total = $this->order->getTotalPrice()->getNumber();
        $order_total = PagarmeUtility::currencyAmountFormat($order_total, $currency_code);
        $rows[] = array($this->t('Valor do pedido'), $order_total);

        $payment_config = $this->order->getData('pagarme_payment_config');
        switch ($this->order->getData('pagarme_payment_method')) {
          case 'boleto':
            $discount = '';
            if ($payment_config['pagarme_boleto_discount'] == 'amount') {
              $discount = $payment_config['pagarme_boleto_discount_amount'];
            } 
            elseif ($payment_config['pagarme_boleto_discount'] == 'percentage') {
              $discount = $payment_config['pagarme_boleto_discount_percentage'] . '%';
            }
            $rows[] = array($this->t('Desconto'), $discount);
            break;
          case 'credit_card':
            $installment_amount = $this->order->getData('pagarme_installments_amount')['installment_amount'];
            $installment = $transaction->getInstallments() . ' x ' . PagarmeUtility::currencyAmountFormat($installment_amount, $currency_code, 'integer');
            $rows[] = array($this->t('Parcelamento'), $installment);
            break;
        }
        $rows[] = array($this->t('Valor da transação'), PagarmeUtility::currencyAmountFormat($transaction->getAmount(), $currency_code, 'integer'));
        // $rows[] = array(t('Taxa da transação'), $transaction->getCost());
        $rows[] = array($this->t('Status da transação'), $status_readable_name[$transaction->getStatus()]);

        if ($transaction->getPaymentMethod() == 'boleto') {
          $boleto_expiration_date = format_date($transaction->getBoletoExpirationDate()->getTimestamp(), 'short');
          $rows[] = array($this->t('Data de vencimento'), $boleto_expiration_date);
          $rows[] = array($this->t('Código de barras'), $transaction->getBoletoBarcode());
          $url = Url::fromUri($transaction->getBoletoUrl());
          $options = array(
            'attributes' => array(
              'target' => '_blank'
            ),
          );
          $url->setOptions($options);
          $boleto_link = Link::fromTextAndUrl($url->toString(), $url)->toString();
          $rows[] = array($this->t('Url do boleto'), $boleto_link);
        }
        $form['transaction'] = array(
          '#type' => 'fieldset',
          '#title' => $this->t('Detalhes da transação'),
        );

        $form['transaction']['detail'] = array(
          '#theme' => 'table',
          '#rows' => $rows
        );
        if ($transaction->getStatus() == 'paid') {
          $destination = drupal_get_destination();
          $uri = '/admin/commerce/orders/'.$transaction_id.'/pagarme/refund';
          $form['transaction_detail']['refund'] = [
            '#title' => $this->t('Efetuar estorno'),
            '#type' => 'link',
            '#url' => Url::fromUri('internal:' . $uri)
          ];
        }

        $rows = array();
        $rows[] = array($this->t('Nome do cliente'), $consumer->name);
        $rows[] = array($this->t('Email'), $consumer->email);
        $gender = '';
        switch ($consumer->gender) {
          case 'M':
            $gender = $this->t('Masculino');
            break;
          case 'F':
            $gender = $this->t('Feminino');
            break;
        }
        $rows[] = array($this->t('Sexo'), $gender);
        $rows[] = array($this->t('CPF/CNPJ'), $consumer->document_number);
        $rows[] = array($this->t('Telefone'), array('data' => $inline_phone));
        $form['customer'] = array(
          '#type' => 'fieldset',
          '#title' => $this->t('Detalhes do cliente'),
        );
        $form['customer']['detail'] = array(
          '#theme' => 'table',
          '#rows' => $rows
        );

        $rows = array();
        $rows[] = array(t('Endereço'), $address->street);
        $rows[] = array(t('Número'), $address->street_number);
        $rows[] = array(t('Complemento'), $address->complementary);
        $rows[] = array(t('Cidade'), $address->city);
        $rows[] = array(t('Estado'), $address->state);
        $rows[] = array(t('País'), $address->country);
        $rows[] = array(t('Cep'), $address->zipcode);
        $form['customer_address'] = array(
          '#type' => 'fieldset',
          '#title' => $this->t('Endereço'),
        );
        $form['customer_address']['detail'] = array(
          '#theme' => 'table',
          '#rows' => $rows
        );
      }
      catch (Exception $e) {
        \Drupal::logger('pagarme')->error($e->getMessage());
      }
    }
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

  }

}
