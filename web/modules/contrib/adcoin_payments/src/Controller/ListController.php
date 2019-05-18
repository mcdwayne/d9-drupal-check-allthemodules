<?php
/**
 * Lists all payment records in the database on the page as a table.
 * @author appels
 */

namespace Drupal\adcoin_payments\Controller;
use Drupal\adcoin_payments\Model\PaymentStorage;
use Drupal\adcoin_payments\Model\Settings;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

class ListController extends ControllerBase {
  public function content() {

    if (!Settings::fetchApiKey()) {
      // No API key warning
      $build['api_key_msg'] = [
        '#markup' => '<div role="contentinfo" aria-label="Error message" class="messages messages--error">'
                    .'<div role="alert">'
                    .'In order to receive payments, please provide a Wallet API key first.'
                    .'</div>'
                    .'</div>',
        '#allowed_tags' => [ 'div' ]
      ];
      return $build;
    }

    // Construct the table header
    $header = [
      ['data' => t('Created At'), 'field' => 't.created_at'],
      ['data' => t('Amount'),     'field' => 't.amount'],
      ['data' => t('Name'),       'field' => 't.name'],
      ['data' => t('Status'),     'field' => 't.status'],
      ['_delete' => ''],
      ['_edit'   => '']
    ];

    // Tell the query object that we're sorting
    $query = \Drupal::database()->select('adcoin_payments', 't')
      ->extend('Drupal\Core\Database\Query\TableSortExtender');
    $query->addField('t', 'payment_id');
    $query->addField('t', 'created_at');
    $query->addField('t', 'amount');
    $query->addField('t', 'name');
    $query->addField('t', 'status');

    // Tell query object how to find header information
    $results = $query
      ->orderByHeader($header)
      ->execute();

    $rows = [];
    foreach ($results as $row) {
      $delete     = Url::fromUserInput('/admin/adcoin-payments/delete/'.$row->payment_id);
      $edit       = Url::fromUserInput('/admin/adcoin-payments/view/'.$row->payment_id);
      $created_at = format_date(strtotime($row->created_at), 'custom', 'j F Y');
      $rows[]     = [
        'created_at' => $created_at,
        'amount'     => $row->amount,
        'name'       => ('' != $row->name) ? $row->name : '<Anonymous>',
        'status'     => PaymentStorage::getStatusText($row->status),

        \Drupal::l('Delete', $delete),
        \Drupal::l('Edit',   $edit)
      ];
    }

    // Build the table for the nice output
    $build['payment_table'] = [
      '#type'   => 'table',
      '#header' => $header,
      '#rows'   => $rows,
      '#empty'  => t('No payments found.')
    ];
    return $build;
  }
}