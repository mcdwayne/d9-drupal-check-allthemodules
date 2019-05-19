<?php

/**
 * @file
 * Views callbacks for Ubercart funds module.
 */

/**
 * Implements hook_views_data().
 */
function ubercart_funds_views_data() {

  $data['uc_funds_transactions'] = [
    'table' => [
      'join' => [
        'uc_funds_user_funds' => [
          'left_field' => 'uid',
          'field' => ['issuer', 'recipient'],
        ],
      ],
    ],
    'issuer_balance' => [
      'relationship' => [
        'group' => t('Ubercart Funds'),
        'label' => t('Issuer balances'),
        'title' => t('Issuer balances'),
        'help' => t('The balance attached to the issuer.'),
        'base' => 'uc_funds_user_funds',
        'base field' => 'uid',
        'relationship field' => 'issuer',
        'id' => 'standard',
      ],
    ],
    'recipient_balance' => [
      'relationship' => [
        'group' => t('Ubercart Funds'),
        'label' => t('Recipient balances'),
        'title' => t('Recipient balances'),
        'help' => t('The balance attached to the recipient.'),
        'base' => 'uc_funds_user_funds',
        'base field' => 'uid',
        'relationship field' => 'recipient',
        'id' => 'standard',
      ],
    ],
    'escrow_operations' => [
      'title' => t('Escrow Operations'),
      'help' => t('Available operations for the escrow payment'),
      'field' => [
        'id' => 'uc_funds_escrow_operations',
        'no group by' => TRUE,
      ],
    ],
    'withdrawal_operations' => [
      'title' => t('Withdrawal Operations'),
      'help' => t('Available operations for withdrawal requests'),
      'field' => [
        'id' => 'uc_funds_withdrawal_operations',
        'no group by' => TRUE,
      ],
    ],
  ];

  $data['uc_funds_user_funds'] = [
    'table' => [
      'group' => t('Ubercart funds'),
      'provider' => 'ubercart_funds',
      'base' => [
        'field' => 'uid',
        'title' => t('User balances'),
        'help' => t('The balances of the users.'),
      ],
      'join' => [
        'users' => [
          'left_field' => 'uid',
          'field' => 'uid',
        ],
      ],
    ],
    'uid' => [
      'title' => t('User ID'),
      'help' => t('The unique user ID.'),
      'relationship' => [
        'group' => t('Users'),
        'label' => t('The user attached to the balance'),
        'title' => t('User of the attached balance'),
        'help' => t('User informations of the attached balance.'),
        'base' => 'users_field_data',
        'base field' => 'uid',
        'relationship field' => 'uid',
        'id' => 'standard',
      ],
    ],
    'balance' => [
      'title' => t('User balance'),
      'help' => t('The balance of the user.'),
      'field' => [
        'id' => 'uc_funds_amount',
        'float' => TRUE,
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
    ],
  ];

  return $data;
}
