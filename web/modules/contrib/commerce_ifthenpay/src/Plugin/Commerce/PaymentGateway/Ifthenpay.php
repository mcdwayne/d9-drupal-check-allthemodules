<?php

namespace Drupal\commerce_ifthenpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\ManualPaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use InvalidArgumentException;

/**
 * Provides the Ifthenpay payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "ifthenpay",
 *   label = "Ifthenpay",
 *   display_label = "Ifthenpay",
 *   modes = {
 *     "n/a" = @Translation("N/A"),
 *   },
 *   forms = {
 *     "add-payment" = "Drupal\commerce_ifthenpay\PluginForm\ManualPaymentAddForm",
 *     "receive-payment" = "Drupal\commerce_ifthenpay\PluginForm\PaymentReceiveForm",
 *   },
 *   payment_type = "payment_manual",
 * )
 */
class Ifthenpay extends PaymentGatewayBase implements ManualPaymentGatewayInterface, SupportsNotificationsInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'instructions' => [
          'value' => '',
          'format' => 'plain_text',
        ],
      ] + parent::defaultConfiguration();
  }
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['multibanco_entidade'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Multibanco - Entidade'),
      '#default_value' => $this->configuration['multibanco_entidade'],
      '#required' => TRUE,
    ];
    $form['multibanco_subentidade'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Multibanco - Subentidade'),
      '#default_value' => $this->configuration['multibanco_subentidade'],
      '#required' => TRUE,
    ];
    $form['multibanco_chaveAntiPhishing'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Chave AntiPhishing'),
      '#default_value' => $this->configuration['multibanco_chaveAntiPhishing'],
      '#required' => TRUE,
    ];

    $form['multibanco_chaveAntiPhishing'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Chave AntiPhishing'),
      '#default_value' => $this->configuration['multibanco_chaveAntiPhishing'],
      '#required' => TRUE,
    ];

    $form['instructions'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Payment instructions'),
      '#description' => $this->t('Shown the end of checkout, after the customer has placed their order.'),
      '#default_value' => $this->configuration['instructions']['value'],
      '#format' => $this->configuration['instructions']['format'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['instructions'] = $values['instructions'];
      $this->configuration['multibanco_entidade'] = $values['multibanco_entidade'];
      $this->configuration['multibanco_subentidade'] = $values['multibanco_subentidade'];
      $this->configuration['multibanco_chaveAntiPhishing'] = $values['multibanco_chaveAntiPhishing'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentInstructions(PaymentInterface $payment) {
    $instructions = [];
    $order_id = $payment->getOrderId();
    $amount = $payment->getAmount();
    $order_value = $amount->getNumber();
    $mb_ref = self::generateMbRef($this->configuration['multibanco_entidade'], $this->configuration['multibanco_subentidade'], $order_id, $order_value);



    if (!empty($this->configuration['instructions']['value'])) {
      $instructions['intro'] = [
        '#type' => 'processed_text',
        '#text' => $this->configuration['instructions']['value'],
        '#format' => $this->configuration['instructions']['format'],
      ];
    }

    $instructions['mb_ref'] = [ '#markup' =>
                    '<p>' . t('Entidade: ') . $this->configuration['multibanco_entidade'] .'</p>' .
                    '<p>' . t('Referência: ') . $mb_ref . '</p>' .
                    '<p>' . t('Montante: ') . $amount . $this->getNotifyUrl()->toString(). '</p>',
    ];


    //Set the Bultibanco Referência as the Payment Remote ID
    $payment->setRemoteId(str_replace(' ', '', $mb_ref));

    //Set the Payment state to 'authorization'
    //$payment->setState('authorization');
    //$payment->save();

    return $instructions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaymentOperations(PaymentInterface $payment) {
    $payment_state = $payment->getState()->value;
    $operations = [];
    $operations['receive'] = [
      'title' => $this->t('Receive'),
      'page_title' => $this->t('Receive payment'),
      'plugin_form' => 'receive-payment',
      'access' => $payment_state == 'pending',
    ];
    $operations['void'] = [
      'title' => $this->t('Void'),
      'page_title' => $this->t('Void payment'),
      'plugin_form' => 'void-payment',
      'access' => $payment_state == 'pending',
    ];
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $received = FALSE) {
    $this->assertPaymentState($payment, ['new']);

    $payment->state = $received ? 'completed' : 'pending';
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function receivePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['pending']);

    // If not specified, use the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $payment->state = 'completed';
    $payment->setAmount($amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $payment->state = 'voided';
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $this->assertRefundAmount($payment, $amount);

    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->state = 'partially_refunded';
    }
    else {
      $payment->state = 'refunded';
    }

    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();
  }

  /**
   * Function coming directly from the vendor Ifthenpay (but with some modifications) with the logic to generate the referências multibanco 9 digit code
   */
  public static function generateMbRef($ent_id, $subent_id, $order_id, $order_value)
  {
    $chk_val = 0;

    $order_id ="0000".$order_id;

    $order_value= sprintf("%01.2f", $order_value);

    $order_value =  self::format_number($order_value);

    //Apenas sao considerados os 4 caracteres mais a direita do order_id
    $order_id = substr($order_id, (strlen($order_id) - 4), strlen($order_id));


    if ($order_value < 1 || $order_value >= 1000000){
      throw new InvalidArgumentException('That order amount is not a valid amount.');
    }

    //cálculo dos check digits

    $chk_str = sprintf('%05u%03u%04u%08u', $ent_id, $subent_id, $order_id, round($order_value*100));

    $chk_array = array(3, 30, 9, 90, 27, 76, 81, 34, 49, 5, 50, 15, 53, 45, 62, 38, 89, 17, 73, 51);

    for ($i = 0; $i < 20; $i++)
    {
      $chk_int = substr($chk_str, 19-$i, 1);
      $chk_val += ($chk_int%10)*$chk_array[$i];
    }

    $chk_val %= 97;

    $chk_digits = sprintf('%02u', 98-$chk_val);

    return $subent_id." ".substr($chk_str, 8, 3)." ".substr($chk_str, 11, 1).$chk_digits;


  }

  /**
   * Function coming directly from the vendor Ifthenpay. I don't think it's necessary bu anyway I'll still use it.
   */
  private function format_number($number)
  {
    $verifySepDecimal = number_format(99,2);

    $valorTmp = $number;

    $sepDecimal = substr($verifySepDecimal, 2, 1);

    $hasSepDecimal = True;

    $i=(strlen($valorTmp)-1);

    for($i;$i!=0;$i-=1)
    {
      if(substr($valorTmp,$i,1)=="." || substr($valorTmp,$i,1)==","){
        $hasSepDecimal = True;
        $valorTmp = trim(substr($valorTmp,0,$i))."@".trim(substr($valorTmp,1+$i));
        break;
      }
    }

    if($hasSepDecimal!=True){
      $valorTmp=number_format($valorTmp,2);

      $i=(strlen($valorTmp)-1);

      for($i;$i!=1;$i--)
      {
        if(substr($valorTmp,$i,1)=="." || substr($valorTmp,$i,1)==","){
          $hasSepDecimal = True;
          $valorTmp = trim(substr($valorTmp,0,$i))."@".trim(substr($valorTmp,1+$i));
          break;
        }
      }
    }

    for($i=1;$i!=(strlen($valorTmp)-1);$i++)
    {
      if(substr($valorTmp,$i,1)=="." || substr($valorTmp,$i,1)=="," || substr($valorTmp,$i,1)==" "){
        $valorTmp = trim(substr($valorTmp,0,$i)).trim(substr($valorTmp,1+$i));
        break;
      }
    }

    if (strlen(strstr($valorTmp,'@'))>0){
      $valorTmp = trim(substr($valorTmp,0,strpos($valorTmp,'@'))).trim($sepDecimal).trim(substr($valorTmp,strpos($valorTmp,'@')+1));
    }

    return $valorTmp;
  }

  /**
   * @inheritDoc
   */
  public function onNotify(Request $request) {

    //There's a problem here if the order id is greater than 4 digits
    $ifthenpay_order_id = substr($request->get('referencia'), 3, 4);

    $ifthenpay_chave = $request->get('chave');
    $order_id = (int) $ifthenpay_order_id;

    if (!empty($this->configuration['multibanco_chaveAntiPhishing']) && $this->configuration['multibanco_chaveAntiPhishing'] != $ifthenpay_chave) {
      // Return empty response with 403 status code.
      return new Response('',403);
    }

    /** @var \Drupal\commerce_payment\PaymentStorage $payment_storage */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    /** @var \Drupal\commerce_payment\Entity\Payment $payment */
    $payment = $payment_storage->loadByRemoteId($request->get('referencia'));

    /** @var \Drupal\commerce_payment\Entity\Payment $payment */
    $payment->setState('completed')->save();

    // Return empty response with 200 status code.
    return new Response();

  }

  /**
   * {@inheritdoc}
   */
  public function getNotifyUrl() {
    return Url::fromRoute('commerce_payment.notify', [
      'commerce_payment_gateway' => $this->entityId,
    ], [
      'absolute' => TRUE,
    ]);
  }
}


