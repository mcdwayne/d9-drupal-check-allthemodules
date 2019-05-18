<?php

namespace Drupal\uc_payment\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_payment\Plugin\PaymentMethodManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays a list of payments attached to an order.
 */
class OrderPaymentsForm extends FormBase {

  /**
   * The order that is being viewed.
   *
   * @var \Drupal\uc_order\OrderInterface
   */
  protected $order;

  /**
   * The payment method manager.
   *
   * @var \Drupal\uc_payment\Plugin\PaymentMethodManager
   */
  protected $paymentMethodManager;

  /**
   * The datetime.time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The date.formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs an OrderPaymentsForm object.
   *
   * @param \Drupal\uc_payment\Plugin\PaymentMethodManager $payment_method_manager
   *   The payment method plugin manager.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The datetime.time service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date.formatter service.
   */
  public function __construct(PaymentMethodManager $payment_method_manager, TimeInterface $time, DateFormatterInterface $date_formatter) {
    $this->paymentMethodManager = $payment_method_manager;
    $this->time = $time;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.uc_payment.method'),
      $container->get('datetime.time'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_payment_by_order_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL) {
    $this->order = $uc_order;

    $form['#attached']['library'][] = 'uc_payment/uc_payment.styles';

    $total = $this->order->getTotal();
    $payments = uc_payment_load_payments($this->order->id());

    $form['order_total'] = [
      '#type' => 'item',
      '#title' => $this->t('Order total'),
      '#theme' => 'uc_price',
      '#price' => $total,
    ];
    $form['payments'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Received'),
        $this->t('User'),
        $this->t('Method'),
        $this->t('Amount'),
        $this->t('Balance'),
        $this->t('Comment'),
        $this->t('Action'),
      ],
      '#weight' => 10,
    ];

    foreach ($payments as $id => $payment) {
      $form['payments'][$id]['received'] = [
        '#markup' => $this->dateFormatter->format($payment->getReceived(), 'short'),
      ];
      $form['payments'][$id]['user'] = [
        '#theme' => 'username',
        '#account' => $payment->getUser(),
      ];
      $form['payments'][$id]['method'] = [
        '#markup' => $payment->getMethod()->getPluginDefinition()['name'],
      ];
      $form['payments'][$id]['amount'] = [
        '#theme' => 'uc_price',
        '#price' => $payment->getAmount(),
      ];
      $total -= $payment->getAmount();
      $form['payments'][$id]['balance'] = [
        '#theme' => 'uc_price',
        '#price' => $total,
      ];
      $form['payments'][$id]['comment'] = [
        '#markup' => $payment->getComment() ?: '-',
      ];
      $form['payments'][$id]['action'] = [
        '#type' => 'operations',
        '#links' => [
          'delete' => [
            'title' => $this->t('Delete'),
            'url' => Url::fromRoute('uc_payments.delete', ['uc_order' => $this->order->id(), 'uc_payment_receipt' => $id]),
          ],
        ],
        '#access' => $this->currentUser()->hasPermission('delete payments'),
      ];
    }

    $form['balance'] = [
      '#type' => 'item',
      '#title' => $this->t('Current balance'),
      '#theme' => 'uc_price',
      '#price' => $total,
    ];

    if ($this->currentUser()->hasPermission('manual payments')) {
      $form['new'] = [
        '#type' => 'details',
        '#title' => $this->t('Add payment'),
        '#open' => TRUE,
        '#weight' => 20,
      ];
      $form['new']['amount'] = [
        '#type' => 'uc_price',
        '#title' => $this->t('Amount'),
        '#required' => TRUE,
        '#size' => 6,
      ];
      $options = array_map(function ($definition) {
        return $definition['name'];
      }, array_filter($this->paymentMethodManager->getDefinitions(), function ($definition) {
        return !$definition['no_ui'];
      }));
      $form['new']['method'] = [
        '#type' => 'select',
        '#title' => $this->t('Payment method'),
        '#options' => $options,
      ];
      $form['new']['comment'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Comment'),
      ];
      $form['new']['received'] = [
        '#type' => 'datetime',
        '#title' => $this->t('Date'),
        '#date_date_element' => 'date',
        '#date_time_element' => 'time',
        '#default_value' => DrupalDateTime::createFromTimestamp($this->time->getRequestTime()),
      ];
      $form['new']['action'] = ['#type' => 'actions'];
      $form['new']['action']['action'] = [
        '#type' => 'submit',
        '#value' => $this->t('Record payment'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $payment = $form_state->getValues();
    uc_payment_enter($this->order->id(), $payment['method'], $payment['amount'], $this->currentUser()->id(), NULL, $payment['comment'], $payment['received']->getTimestamp());
    $this->messenger()->addMessage($this->t('Payment entered.'));
  }

}
