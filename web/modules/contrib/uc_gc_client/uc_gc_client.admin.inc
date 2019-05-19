<?php

/**
 * @file
 * The administration forms, and their validate and submit functions.
 */

use Drupal\node\NodeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_form_FORM_ID_alter().
 */
function uc_gc_client_form_node_form_alter(&$form, FormStateInterface $form_state) {

  if (!uc_product_is_product_form($form_state)) {
    return;
  }

  $nid = $form_state->getFormObject()->getEntity()->id();

  if (isset($nid)) {
    $defaults = db_select('uc_gc_client_products', 'p')
      ->fields('p')
      ->condition('nid', $nid)
      ->execute()->fetch();
  }
  !empty($defaults) ? $use = (int) $defaults->gc_use : $use = FALSE;
  !empty($defaults) ? $type = $defaults->type : $type = 'P';
  !empty($defaults) ? $payment = $defaults->create_payment : $payment = 0;
  !empty($defaults) ? $dom = $defaults->dom : $dom = NULL;
  !empty($defaults) ? $start_date = $defaults->start_date : $start_date = NULL;
  !empty($defaults) ? $int_length = $defaults->interval_length : $int_length = NULL;
  !empty($defaults) ? $int_unit = $defaults->interval_unit : $int_unit = t('- None -');
  !empty($defaults) ? $price_x = $defaults->price_x : $price_x = 1;

  $form['#attached']['library'][] = 'uc_gc_client/gc_settings_form';
  $form['gc'] = [
    '#type' => 'details',
    '#title' => t('GoCardless Settings'),
    '#open' => TRUE,
    '#group' => 'advanced',
    '#tree' => TRUE,
    '#weight' => 100,
  ];
  $form['gc']['gc_use'] = [
    '#type' => 'checkbox',
    '#title' => t('Use GoCardless'),
    '#default_value' => $use,
  ];
  $form['gc']['container'] = [
    '#type' => 'container',
    '#parents' => ['gc'],
    '#states' => [
      'invisible' => [
        'input[name="gc[gc_use]"]' => ['checked' => FALSE],
      ],
    ],
  ];
  $options = ['P' => t('One-off payments'), 'S' => t('Subscription')];
  $form['gc']['container']['type'] = [
    '#type' => 'radios',
    '#title' => t('Type'),
    '#options' => $options,
    '#default_value' => $type,
    '#description' => t('Select the type that will be used for new GoCardless orders.'),
  ];

  $form['gc']['container']['create_payment'] = [
    '#type' => 'checkbox',
    '#prefix' => t('<b>Create payment immediately</b>'),
    '#default_value' => $payment,
    '#title' => t("Automatically create a payment upon completeion of checkout."),
    '#description' => t("If 'One-off payments' then a payment will be automatically created at completion of checkout. If 'Subscription', then the new subscription will be instructed to create a payment at the earliest possible time. Leave unselected if you want another module to handle creating the initial payment, or you wish to create payments manually."),
  ];

  $form['gc']['container']['dom'] = [
    '#type' => 'textfield',
    '#title' => t('Day(s) of month'),
    '#default_value' => $dom,
    '#size' => 15,
    '#maxlength' => 15,
    '#description' => t("Enter one or more days of the month, on which the first payment may be created. Upon checkout the next available date will be chosen from those provided. Multiple values must be seperated by a comma. Overrides 'Create payment immediately'."),
    '#attributes' => ['class' => ['gc-settings-form-field-length']],
    '#required' => FALSE,
    '#states' => [
      'visible' => [
        ':input[name="gc[create_payment]"]' => ['checked' => FALSE],
      ],
    ],
  ];

  $form['gc']['container']['start_date'] = [ 
    '#title' => t('Start date'),
    '#type' => 'date',
    '#default_value' => $start_date,
    '#description' => t("Specify a date for the first payment creation (usefull for product launches). Is ignored if the date is in the past. Overrides 'Create payment immediately', and 'Day of month'."),
    '#states' => [
      'visible' => [
        ':input[name="gc[create_payment]"]' => ['checked' => FALSE],
        ':input[name="gc[dom]"]' => ['value' => ''],
      ],
    ],
  ];

  $form['gc']['container']['int_length'] = [
    '#type' => 'number',
    '#title' => t('Interval length between payments'),
    '#default_value' => $int_length,
    '#size' => 1,
    '#min' => 1,
    '#max' => 52,
    '#description' => t("The default interval length between direct debit payments. "),
    '#attributes' => ['class' => ['gc-settings-form-number-length']],
    '#required' => FALSE,
  ];

  $form['gc']['container']['int_unit'] = [
    '#type' => 'select',
    '#title' => t('Interval unit between payments'),
    '#options' => [
      'day' => t('day'),
      'week' => t('week'),
      'month' => t('month'),
      'year' => t('year'),
    ],
    '#default_value' => $int_unit,
    "#empty_option" => t('- None -'),
    '#description' => t("The default interval unit between direct debit payments."),
    '#required' => FALSE,
  ];

  $form['gc']['container']['markup'] = [
    '#type' => 'value',
    '#markup' => t('<b>Interval length and unit:</b> These settings are not required. However if the type is Subscription, then you must either provide an Interval length and unit here, or else set up an Interval attribute instead. Any values set here will be overridden if the Interval attribute is set for the product as well. Using the Interval attribute can give customers more choice.'),
  ];

  $form['gc']['container']['price_x'] = [
    '#type' => 'number',
    '#step' => .01,
    '#min' => .01,
    '#title' => t('Price multiplier'),
    '#default_value' => $price_x,
    '#description' => t('Set a multiplier for one-off payments or subscriptions. Use in cases where the GoCardless amount is different to the product price, for example a monthly subscription for a weekly price. Leave as 1 if the GoCardless amount and the price are the same.'),
    '#attributes' => ['class' => ['gc-settings-form-number-length']],
  ];

  foreach (array_keys($form['actions']) as $action) {
    if ($action != 'preview' && isset($form['actions'][$action]['#type']) && $form['actions'][$action]['#type'] === 'submit') {
      $form['actions'][$action]['#submit'][] = 'uc_gc_client_product_submit';
    }
  }
  $form['#validate'][] = 'uc_gc_client_product_validate';
}

/**
 * Form validate callback.
 */
function uc_gc_client_product_validate($form, FormStateInterface $form_state) {

  $nid = $form_state->getFormObject()->getEntity()->id();
  $gc = $form_state->getValue(['gc']);

  if($gc['dom']) {
    if (!preg_match('/^\d+(?:,\d+)*$/', $gc['dom'])) {
      $form_state->setErrorByName('gc][dom', t('Day(s) of month can only contain numbers and commas.'));
    }
  }

  if ($gc['int_unit'] == 'day' && $gc['type'] == 'S') {
    $form_state->setErrorByName('gc][int_unit', t("Only 'week', 'month', and 'year' can be used as interval length for subscription payments."));
  }

  if ($gc['gc_use']) {
    if (empty($gc['int_length']) && !empty($gc['int_unit'])) {
      $form_state->setErrorByName('gc][int_length', t('Interval length must be set as well as Interval unit.'));
    }
    elseif (!empty($gc['int_length']) && empty($gc['int_unit'])) {
      $form_state->setErrorByName('gc][int_unit', t('Interval unit must be set as well as Interval length.'));
    }
  }
}

/**
 * Form submit callback.
 */
function uc_gc_client_product_submit(array $form, FormStateInterface $form_state) {

  $nid = $form_state->getValue(['nid']);
  $gc = $form_state->getValue(['gc']);
 
 if ($gc['type'] == 'S' && empty($gc['int_length'])) {

    $query = db_select('uc_attributes', 'a');
    $query->join('uc_product_attributes', 'p', 'a.aid = p.aid');
    $check = $query
      ->fields('a')
      ->fields('p')
      ->condition('a.name', 'Interval')
      ->condition('p.nid', $nid)
      ->execute()->fetch();

    if (empty($check)) {
      // Provide a friendly warning if the Interval Attribute is not set.
      drupal_set_message(t('You have chosen Subscription type but have not provided an Interval length and unit. You must either provide an Interval length and unit, or add an Interval attribute to the product. '), 'warning');
    }
  }

  if ($gc['gc_use']) {
    db_merge('uc_gc_client_products')
      ->key(['nid' => $nid])
      ->fields([
        'nid' => $nid,
        'gc_use' => 1,
        'type' => $gc['type'],
        'create_payment' => $gc['create_payment'],
        'dom' => !is_null($gc['dom']) ? $gc['dom'] : NULL,
        'start_date' => !is_null($gc['start_date']) ? $gc['start_date'] : NULL,
        'interval_length' => !empty($gc['int_length']) ? $gc['int_length'] : NULL,
        'interval_unit' => !empty($gc['int_unit']) ? $gc['int_unit'] : NULL,
        'price_x' => !empty($gc['price_x']) ? $gc['price_x'] : NULL,
      ])
      ->execute();
  }
  else {
    db_update('uc_gc_client_products')
      ->fields([
        'gc_use' => 0,
      ])
      ->condition('nid', $nid)
      ->execute();
  }
}

/**
 * Implements hook_node_delete().
 *
 * Remove product record from gc_client_products table in database.
 */
function uc_gc_client_node_delete(NodeInterface $node) {

  if (uc_product_is_product($node)) {
    $delete = db_delete('uc_gc_client_products')
      ->condition('nid', $node->id(), '=')
      ->execute();
  }
}

/**
 * Implements hook_field_extra_fields().
 *
 * Adds the "Product information".
 */
function uc_gc_client_entity_extra_field_info() {

  $extra = [];
  foreach (uc_product_types() as $type) {
    $extra['node'][$type] = [
      'display' => [
        'type' => [
          'label' => t('GoCardless type'),
          'description' => t('Subscription or pre-authorization'),
          'weight' => 0,
        ],
        'start_date' => [
          'label' => t('Start date for new mandate'),
          'description' => t('Start date for new mandate description'),
          'weight' => 2,
        ],
      ],
    ];
  }
  return $extra;
}
