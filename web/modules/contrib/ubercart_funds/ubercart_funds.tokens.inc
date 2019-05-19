<?php

/**
 * @file
 * TOken hooks for ubercart_funds module.
 */

use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function ubercart_funds_token_info() {
  $transaction_type = [
    'name' => t('transaction'),
    'description' => t('Tokens related to transactions.'),
    'needs-data' => 'transaction',
  ];

  $transaction = [];
  $transaction['date'] = [
    'name' => t('Date'),
    'description' => t('Date when the transaction was issued.'),
  ];
  $transaction['brut-amount'] = [
    'name' => t('Brut amount'),
    'description' => t('Brut amount of the transaction.'),
  ];
  $transaction['commission'] = [
    'name' => t('Commission fees'),
    'description' => t('The commission taken on the transaction.'),
  ];
  $transaction['net-amount'] = [
    'name' => t('Net amount'),
    'description' => t('Net amount of the transaction.'),
  ];
  $transaction['type'] = [
    'name' => t('Type'),
    'description' => t('The type of the transaction.'),
  ];
  $transaction['status'] = [
    'name' => t('Status'),
    'description' => t('The status of the transaction.'),
  ];
  $transaction['notes'] = [
    'name' => t('Notes'),
    'description' => t('The notes left by the issuer.'),
  ];

  $withdrawal_request_type = [
    'name' => t('Withdrawal request'),
    'description' => t('Tokens related to withdrawal requests.'),
    'needs-data' => 'withdrawal_request',
  ];
  $withdrawal_request['date'] = [
    'name' => t('Date'),
    'description' => t('Date when the withdrawal was issued.'),
  ];
  $withdrawal_request['method'] = [
    'name' => t('Method'),
    'description' => t('Payment method requested by the issuer.'),
  ];
  $withdrawal_request['brut-amount'] = [
    'name' => t('Brut amount'),
    'description' => t('Brut amount of the withdrawal.'),
  ];
  $withdrawal_request['commission'] = [
    'name' => t('Commission fees'),
    'description' => t('The commission taken on the withdrawal.'),
  ];
  $withdrawal_request['net-amount'] = [
    'name' => t('Net amount'),
    'description' => t('Net amount of the withdrawal.'),
  ];
  $withdrawal_request['status'] = [
    'name' => t('Status'),
    'description' => t('The status of the withdrawal.'),
  ];
  $withdrawal_request['reason'] = [
    'name' => t('Declined reason'),
    'description' => t('The reason why the withdrawal has been declined.'),
  ];

  return [
    'types' => [
      'transaction' => $transaction_type,
      'withdrawal_request' => $withdrawal_request_type,
    ],
    'tokens' => [
      'transaction' => $transaction,
      'withdrawal_request' => $withdrawal_request,
    ],
  ];
}

/**
 * Implements hook_tokens().
 */
function ubercart_funds_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {

  if (isset($options['langcode'])) {
    $langcode = $options['langcode'];
  }
  else {
    $langcode = LanguageInterface::LANGCODE_DEFAULT;
  }

  $replacements = [];

  if ($type == 'transaction' && !empty($data['transaction'])) {
    $transaction = $data['transaction'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'date':
          $date_format = DateFormat::load('short');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($transaction->created, 'medium', '', NULL, $langcode);
          break;

        case 'brut-amount':
          $replacements[$original] = uc_currency_format($transaction->brut_amount / 100);
          break;

        case 'commission':
          $replacements[$original] = uc_currency_format($transaction->commission / 100);
          break;

        case 'net-amount':
          $replacements[$original] = uc_currency_format($transaction->net_amount / 100);
          break;

        case 'type':
          $replacements[$original] = $transaction->type;
          break;

        case 'status':
          $replacements[$original] = $transaction->status;
          break;

        case 'notes':
          $replacements[$original] = $transaction->notes;
          break;
      }
    }
  }

  if ($type == 'withdrawal_request' && !empty($data['withdrawal_request'])) {
    $request = $data['withdrawal_request'];

    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'date':
          $date_format = DateFormat::load('short');
          $bubbleable_metadata->addCacheableDependency($date_format);
          $replacements[$original] = \Drupal::service('date.formatter')->format($request->created, 'medium', '', NULL, $langcode);
          break;

        case 'brut-amount':
          $replacements[$original] = $request->brut_amount / 100;
          break;

        case 'commission':
          $replacements[$original] = uc_currency_format($request->commission / 100);
          break;

        case 'net-amount':
          $replacements[$original] = uc_currency_format($request->net_amount / 100);
          break;

        case 'method':
          $replacements[$original] = $request->method;
          break;

        case 'status':
          $replacements[$original] = $request->status;
          break;

        case 'reason':
          $replacements[$original] = $request->reason;
          break;
      }
    }
  }

  return $replacements;
}
