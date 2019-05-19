<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * Xero Expense type.
 *
 * @DataType(
 *   id = "xero_expense",
 *   label = @Translation("Xero Expense Claim"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\ExpenseDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class Expense extends XeroTypeBase {

  static public $guid_name = 'ExpenseClaimID';
  static public $xero_name = 'ExpenseClaim';
  static public $plural_name = 'ExpenseClaims';
  static public $label = 'ExpenseClaimID';

  /**
   * {@inheritdoc}
   */
  public function view() {
    $payment_rows = [];
    $payment_header = [
      $this->t('Type'),
      $this->t('Status'),
      $this->t('Amount'),
      $this->t('Date'),
    ];
    $className = substr($this->getName(), 5);

    $build = [
      '#theme' => $this->getName(),
      '#expense' => $this->getValue(),
      '#user' => $this->get('User')->view(),
      '#receipts' => [
        '#type' => 'container',
       ],
      '#payments' => [
        '#theme' => 'table',
        '#header' => $payment_header,
      ],
      '#attributes' => [
        'class' => ['xero-item', 'xero-item--' . $className],
      ],
    ];

    foreach ($this->get('Receipts') as $n => $receipt) {
      /** @var \Drupal\xero\Plugin\DataType\Receipt $receipt */
      $build['#recepts'][] = $receipt->view();
    }

    foreach ($this->get('Payments') as $payment) {
      /** @var \Drupal\xero\Plugin\DataType\Payment $payment */
      $payment_rows[] = [
        $payment->get('PaymentType')->getString(),
        $payment->get('Status')->getString(),
        $payment->get('Amount')->getString(),
        $payment->get('Date')->getString(),
      ];
    }

    $build['#payments']['#rows'] = $payment_rows;

    return $build;
  }
}
