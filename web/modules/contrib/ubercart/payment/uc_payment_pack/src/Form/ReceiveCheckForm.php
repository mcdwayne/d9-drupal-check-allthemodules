<?php

namespace Drupal\uc_payment_pack\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\uc_order\OrderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for recording a received check and expected clearance date.
 */
class ReceiveCheckForm extends FormBase {

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
   * Form constructor.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The datetime.time service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date.formatter service.
   */
  public function __construct(TimeInterface $time, DateFormatterInterface $date_formatter) {
    $this->time = $time;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('datetime.time'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'uc_payment_pack_receive_check_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, OrderInterface $uc_order = NULL) {
    $balance = uc_payment_balance($uc_order);
    $form['balance'] = [
      '#prefix' => '<strong>' . $this->t('Order balance:') . '</strong> ',
      '#markup' => uc_currency_format($balance),
    ];
    $form['order_id'] = [
      '#type' => 'hidden',
      '#value' => $uc_order->id(),
    ];
    $form['amount'] = [
      '#type' => 'uc_price',
      '#title' => $this->t('Check amount'),
      '#default_value' => $balance,
    ];
    $form['comment'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Comment'),
      '#description' => $this->t('Any notes about the check, like type or check number.'),
      '#size' => 64,
      '#maxlength' => 256,
    ];
    $form['clear_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Expected clear date'),
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
      '#default_value' => DrupalDateTime::createFromTimestamp($this->time->getRequestTime()),
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Receive check'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    uc_payment_enter($form_state->getValue('order_id'), 'check', $form_state->getValue('amount'), $this->currentUser()->id(), NULL, $form_state->getValue('comment'));

    $clear_date = $form_state->getValue('clear_date')->getTimestamp();
    db_insert('uc_payment_check')
      ->fields([
        'order_id' => $form_state->getValue('order_id'),
        'clear_date' => $clear_date,
      ])
      ->execute();
    $this->messenger()->addMessage($this->t('Check received, expected clear date of @date.', ['@date' => $this->dateFormatter->format($clear_date, 'uc_store')]));

    $form_state->setRedirect('entity.uc_order.canonical', ['uc_order' => $form_state->getValue('order_id')]);
  }

}
