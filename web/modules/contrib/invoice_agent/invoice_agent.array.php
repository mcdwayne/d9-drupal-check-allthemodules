<?php

/**
 * @file
 * Defines the configuration arrays.
 */

/**
 * The list of the possible documents.
 */
function invoice_agent__invoice_types() {
  return [
    'imprest_account' => t('Imprest account'),
    'invoice' => t('Invoice'),
    'final_invoice' => t('Final invoice'),
    'corrective_invoice' => t('Corrective invoice'),
    'prepaid_request' => t('Prepaid request'),
    'delivery_note' => t('Delivery note'),
  ];
}

/**
 * The full list of the possible documents.
 */
function invoice_agent__invoice_types_full() {
  return array_merge(['nothing' => t('Do nothing')], invoice_agent__invoice_types());
}

/**
 * The common properties of documents.
 */
function invoice_agent__common_properties() {
  return [
    'e_invoice' => [
      '#title' => t('E-invoice'),
      '#type' => 'checkbox',
      '#description' => t('This affects the default setting of an electronic or paper-based document.'),
    ],
    'prefix' => [
      '#title' => t('Prefix'),
      '#type' => 'textfield',
      '#maxlength' => 5,
      '#description' => t('The prefix defined on szamlazz.hu.'),
    ],
    'deadline' => [
      '#title' => t('Payment deadline'),
      '#type' => 'number',
      '#required' => TRUE,
      '#description' => t('Number of days to payment deadline.'),
      '#min' => 1,
      '#maxlength' => 2,
    ],
    'payment' => [
      '#type' => 'textfield',
      '#title' => t('Payment mode'),
      '#required' => TRUE,
      '#maxlength' => 60,
      '#description' => t('Default payment mode.'),
    ],
    'note' => [
      '#title' => t('Note'),
      '#type' => 'textarea',
      '#description' => t('Note, it will appear on the created document.'),
    ],
    'notification' => [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => t('Customer notification by e-mail'),
      '#options' => [
        'n' => t('Do not send any notification to the customer. (not recommended)'),
        't' => t('Szamlazz.hu send notification with the subject and body set there.'),
        'h' => t('Szamlazz.hu send notification with the subject and body set here.'),
        's' => t('Invoice Agent module send notification.'),
      ],
      '#description' => t('Who should notify the customer that the document has been made?'),
    ],
    'notification_subject' => [
      '#type' => 'textfield',
      '#title' => t('Subject'),
      '#maxlength' => 180,
      '#description' => t('Notification email subject.'),
    ],
    'notification_body' => [
      '#type' => 'textarea',
      '#title' => t('Body'),
      '#rows' => 10,
      '#description' => t('Notification email body.'),
    ],
    'store' => [
      '#title' => t('Save document as media'),
      '#type' => 'checkbox',
      '#description' => t('If this is enabled, then the document will be stored to local file system. ATTENTION! If you use this feature, then do not forget to set the correct access permissions. The author of the media will be the same as the author of the order. If the purchase without registration is allowed, the author of the media may be anonymous too.'),
    ],
    'attach' => [
      '#title' => t('Attach the document'),
      '#type' => 'checkbox',
      '#description' => t('If this is enabled, then the document will be sent as an attachment to the email.'),
    ],
    'remove' => [
      '#title' => t('Remove free items'),
      '#type' => 'checkbox',
      '#description' => t('This will affect the removal of free items from the invoices.'),
    ],
    'post' => [
      '#title' => t('Posting'),
      '#type' => 'checkbox',
      '#description' => t('Post the invoice to the customer by szamlazz.hu.'),
    ],
  ];
}

/**
 * The main configuration settings.
 */
function invoice_agent__config_items() {
  $return = [
    'on_save' => [
      '#title' => t('Create invoices immediately'),
      '#type' => 'checkbox',
      '#description' => t('If this is enabled, then the invoices will be created when the order is saved.'),
    ],
    'by_cron' => [
      '#title' => t('Create invoices by cron'),
      '#type' => 'checkbox',
      '#description' => t('If this switch is on, orders will be processed by cron. Recommended.'),
    ],
    'batch_size' => [
      '#title' => t('Batch size'),
      '#type' => 'number',
      '#required' => TRUE,
      '#description' => t('How many orders should be processed by each cron run? Set to 0 to process all orders, but that is not recommended. The recommended value is 1.'),
      '#maxlength' => 2,
      '#states' => [
        'invisible' => [
          'input[name="by_cron"]' => ['checked' => FALSE],
        ],
        'required' => [
          'input[name="by_cron"]' => ['checked' => TRUE],
        ],
      ],
    ],
    'api_username' => [
      '#title' => t('Your szamlazz.hu account e-mail'),
      '#type' => 'email',
      '#required' => TRUE,
      '#description' => t('The email address you registered to szamlazz.hu.'),
      'placeholder' => TRUE,
    ],
    'update_password' => [
      '#title' => t('Update password'),
      '#type' => 'checkbox',
      '#description' => t('Check this checkbox if you are updating your stored password.'),
      '#default_value' => FALSE,
      'condition' => 'api_password',
    ],
    'api_password' => [
      '#title' => t('Your szamlazz.hu account password'),
      '#type' => 'password',
      '#required' => '!$config->get(\'api_password\');',
      '#description' => t('The password you registered to szamlazz.hu.'),
      '#states' => [
        'invisible' => [
          'input[name="update_password"]' => ['checked' => FALSE],
        ],
        'required' => [
          'input[name="update_password"]' => ['checked' => TRUE],
        ],
      ],
      'placeholder' => TRUE,
    ],
    'bank_name' => [
      '#title' => t('Your bank name'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#description' => t('The name of the bank to write to header of invoices.'),
      'placeholder' => TRUE,
    ],
    'bank_account' => [
      '#title' => t('Your bank account number'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#description' => t('Your bank account number to write to header of invoices.'),
      'placeholder' => TRUE,
    ],
    'taxfree' => [
      '#title' => t('Tax exemption (AAM)'),
      '#type' => 'checkbox',
      '#description' => t('If you are subject to tax exemptions, please turn this chekcbox on. In this case, AAM is used as a tax rate.'),
    ],
    'paid' => [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => t('Paid order (eg. PayPal)'),
      '#options' => invoice_agent__invoice_types_full(),
      '#description' => t('What document should be made when the end of the order is paid.'),
      'group' => 'completed',
      'details' => ['completed' => t('Order completed behavior')],
    ],
    'unpaid' => [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => t('Unpaid order (eg. bank transfer)'),
      '#options' => invoice_agent__invoice_types_full(),
      '#description' => t('What document should be made when the end of the order is unpaided.'),
      'details' => ['completed' => t('Order completed behavior')],
    ],
    'close' => [
      '#type' => 'radios',
      '#required' => TRUE,
      '#title' => t('Closing the payment (eg. bank transfer arrived)'),
      '#options' => invoice_agent__invoice_types_full(),
      '#description' => t('What document should be made when closing the payment.'),
      'details' => ['completed' => t('Order completed behavior')],
    ],
  ];
  foreach (invoice_agent__invoice_types() as $type_key => $type) {
    foreach (invoice_agent__common_properties() as $property_key => $property) {
      $property['details'] = [$type_key => $type];
      $return["{$type_key}_{$property_key}"] = $property;
    }
  }
  return $return;
}
