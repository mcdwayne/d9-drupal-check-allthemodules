<?php

namespace Drupal\trustpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "trustcard",
 *   label = "Trustcard",
 *   display_label = "TrustCard",
 *   forms = {
 *     "offsite-payment" = "Drupal\trustpay\PluginForm\OffsiteRedirect\TrustcardForm",
 *   },
 *   payment_method_types = {"trustpay"},
 *   modes = {"test", "live"},
 * )
 */
class Trustcard extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'redirect_method' => '',
      'aid' => '',
      'secret_key' => '',
      'countries' => [
        'cz' => 0,
        'hu' => 0,
        'pl' => 0,
        'sk' => 0,
        'ee' => 0,
        'bg' => 0,
        'ro' => 0,
        'ba' => 0,
        'hr' => 0,
        'rs' => 0,
        'lv' => 0,
        'lt' => 0,
        'si' => 0,
      ]
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // A real gateway would always know which redirect method should be used,
    // it's made configurable here for test purposes.

    $form['redirect_method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Redirect method'),
      '#options' => [
        'get' => $this->t('Redirect via GET (302 header)'),
        'post' => $this->t('Redirect via POST'),
      ],
      '#default_value' => $this->configuration['redirect_method'],
      '#required' => TRUE,
    ];

    $form['aid'] = [
      '#type' => 'textfield',
      '#title' => t('Merchant account ID (AID)'),
      '#description' => t('ID of account assigned by TrustPay.'),
      '#default_value' => $this->configuration['aid'],
      '#required' => TRUE,
    ];
    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => t('Secret key'),
      '#description' => t('The key used for signing data is obtained by Merchant when the agreement with TrustPay is signed.'),
      '#default_value' => $this->configuration['secret_key'],
      '#required' => TRUE,
    ];

    $form['countries'] = [
      '#type' => 'fieldset',
      '#title' => t('Choose allowed countries for the payment process.'),
      '#attributes' => [
        'class' => [
          'collapsible', 'collapsed',
        ]
      ],
    ];
    $form['countries']['cz'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow Czech Republic'),
      '#default_value' => $this->configuration['countries']['cz'],
    ];
    $form['countries']['hu'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow Hungary'),
      '#default_value' => $this->configuration['countries']['hu'],
    ];
    $form['countries']['pl'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow Poland'),
      '#default_value' => $this->configuration['countries']['pl'],
    ];
    $form['countries']['sk'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow Slovak Republic'),
      '#default_value' => $this->configuration['countries']['sk'],
    ];
    $form['countries']['ee'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow Estonia'),
      '#default_value' => $this->configuration['countries']['ee'],
    ];
    $form['countries']['bg'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow Bulgaria'),
      '#default_value' => $this->configuration['countries']['bg'],
    ];
    $form['countries']['ro'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow Romania'),
      '#default_value' => $this->configuration['countries']['ro'],
    ];
    $form['countries']['ba'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow Bosnia and Herzegovina'),
      '#default_value' => $this->configuration['countries']['ba'],
    ];
    $form['countries']['hr'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow Croatia'),
      '#default_value' => $this->configuration['countries']['hr'],
    ];
    $form['countries']['rs'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow Serbia'),
      '#default_value' => $this->configuration['countries']['rs'],
    ];
    $form['countries']['lv'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow Latvia'),
      '#default_value' => $this->configuration['countries']['lv'],
    ];
    $form['countries']['lt'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow Lithuania'),
      '#default_value' => $this->configuration['countries']['lt'],
    ];
    $form['countries']['si'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow Slovenia'),
      '#default_value' => $this->configuration['countries']['si'],
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
      $this->configuration['redirect_method'] = $values['redirect_method'];
      $this->configuration['aid'] = $values['aid'];
      $this->configuration['secret_key'] = $values['secret_key'];
      $this->configuration['countries']['cz'] = $values['countries']['cz'];
      $this->configuration['countries']['hu'] = $values['countries']['hu'];
      $this->configuration['countries']['pl'] = $values['countries']['pl'];
      $this->configuration['countries']['sk'] = $values['countries']['sk'];
      $this->configuration['countries']['ee'] = $values['countries']['ee'];
      $this->configuration['countries']['bg'] = $values['countries']['bg'];
      $this->configuration['countries']['ro'] = $values['countries']['ro'];
      $this->configuration['countries']['ba'] = $values['countries']['ba'];
      $this->configuration['countries']['hr'] = $values['countries']['hr'];
      $this->configuration['countries']['rs'] = $values['countries']['rs'];
      $this->configuration['countries']['lv'] = $values['countries']['lv'];
      $this->configuration['countries']['lt'] = $values['countries']['lt'];
      $this->configuration['countries']['si'] = $values['countries']['si'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    // @todo Add examples of request validation.
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'pending',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
    ]);
    $payment->save();
    $message = \Drupal::messenger();
    $message->addMessage('Payment was processed');
  }

  /**
   * @inheritdoc
   */
  public function onNotify(Request $request) {

    // Get query parameters from request.
    $request_parameters = $request->query->all();

    // Get Order ID from request and load Order.
    $order_id = $request_parameters['REF'];
    $order = Order::load($order_id);

    // Verify if the transaction is coming from the Trustpay!
    $aid = $this->getConfiguration()['aid'];
    $typ = $request_parameters['TYP'];

    $currency_code = $order->getTotalPrice()->getCurrencyCode();
    $order_total = number_format($order->getTotalPrice()->getNumber(), 2, '.', '');

    // Set currency from the currency of the order total
    // Check if the currency of the order total is supported by Trustpay - if not return error !!!
    $cur = $currency_code;
    $ref = $order->id();
    $res = $request_parameters['RES'];
    $tid = $request_parameters['TID'];
    $oid = $request_parameters['OID'];
    $tss = $request_parameters['TSS'];

    // Set message string in order which is required by Trustpay
    $message = $aid . $typ . $order_total . $cur . $ref . $res . $tid . $oid . $tss;
    $key = $this->getConfiguration()['secret_key'];

    \Drupal::logger('values to SIGN')->notice($aid . '||' . $typ . '||' . $order_total . '||' . $cur . '||' . $ref . '||' . $res . '||' . $tid . '||' . $oid . '||' . $tss . '!!' . $key, []);

    // Check if the return SIGN equals our TEST
    $checksign = getSign($key, $message);

    \Drupal::logger('return SIG TEST')->notice(print_r($checksign, TRUE), []);
    if (isset($request_parameters['SIG']) && $request_parameters['SIG'] == $checksign) {
      \Drupal::logger('TRUSTPAY')->notice('Payment notification SIG check OK!', []);

      if ($request_parameters['RES'] == 0) {
        $state = 'completed';
      }
      else {
        $state = 'pending';
      }

      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

      // Load order payments.
      /** @var \Drupal\commerce_payment\Entity\Payment $payment */
      $payment = reset($payment_storage->loadByProperties(['order_id' => $order_id]));

      $payment->setState($state)
        ->setAmount($order->getTotalPrice());
      $payment->save();
    }
    else {
      \Drupal::logger('TRUSTPAY')->notice('Payment notification not from TRUSTPAY!', []);
    }
  }

}
