<?php
/**
 * @file
 * Provide views data and handlers for balance_tracker.module
 */

/**
 * Implements of hook_views_data().
 */
function balance_tracker_views_data() {
  // balance_items table.
  $data['balance_items']['table']['group'] = t('Balances');

  $data['balance_items']['table']['join'] = array(
    // ...to the user table.
    'users' => array(
      'left_field' => 'uid',
      'field' => 'uid',
    ),
  );

  $data['balance_items']['table']['base'] = array(
    'field' => 'bid',
    'title' => t('Balance item'),
    'help' => t('Balances are amounts credited or debited to a user.'),
  );

  // bid.
  $data['balance_items']['bid'] = array(
    'title' => t('Balance ID'),
    'help' => t('The unique identifier for this balance item.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // user.
  $data['balance_items']['uid'] = array(
    'title' => t('User'),
    'help' => t('The ID of the user this item belongs to.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'relationship' => array(
      'id' => 'standard',
      'base' => 'users',
      'field' => 'uid',
    ),
  );

  // timestamp.
  $data['balance_items']['timestamp'] = array(
    'title' => t('Timestamp'),
    'help' => t('The timestamp this item was created.'),
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
  );

  // type.
  $data['balance_items']['type'] = array(
    'title' => t('Type'),
    'help' => t('Whether this item is a credit or debit.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'balance_items_type',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // message.
  $data['balance_items']['message'] = array(
    'title' => t('Message'),
    'help' => t('Log message related to this item.'),
    'field' => array(
      'id' => 'markup',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  // amount.
  $data['balance_items']['amount'] = array(
    'title' => t('Amount'),
    'help' => t('The amount credited or debited with this transaction.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  // balance.
  $data['balance_items']['balance'] = array(
    'title' => t('Balance'),
    'help' => t('The balance after a transaction has taken place.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  return $data;
}
