<?php
/**
 * @file
 * Functions that modify booking-related node forms.
**/

/**
 * CONTENTS
 *
 * all_booking_form_alter()
 * be_booking_form_alter()
 * fe_booking_form_alter()
 * line_item_form_alter()
**/

use Drupal\Core\Form\FormStateInterface;


/**
 * Edits all booking forms.
 */
function all_booking_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // kint($form, '$form');
  // kint($form_state, '$form_state');
  $build_info_args = $form_state->getBuildInfo()['args'];

  $bookable_nid = \Drupal::config('abookings.bookingsettings')->get('bookable');

  $form['field_history']['#access'] = FALSE;

  // If this site represents just a single bookable
  if ($bookable_nid) {
    $form['field_bookable_unit']['widget']['#options'] = [$bookable_nid => 'Bookable unit'];
    $form['field_bookable_unit']['widget']['#default_value'] = [$bookable_nid];
    $form['field_bookable_unit']['#attributes']['class'][] = 'hidden';
    // kint($form, '$form');
  }

  // Unneeded node settings
  $form['revision_information']['#access'] = FALSE;

  // Add custom validate / submit callback function
  $form['actions']['publish']['#submit'][] = 'abookings_node_form_submit';
  $form['#validate'][] = 'booking_form_validate';

  // Add button for validating the promo
  $form['field_promo_code_provided']['widget'][0]['validate_button'] = [
    '#type' => 'button',
    '#value' => t('Validate'),
    '#attributes' => [
      'id' => 'validate_promo',
      'class' => ['right align-right'],
    ],
    '#weight' => -1,
  ];
  $form['field_promo_code_provided']['widget'][0]['discount'] = [
    '#markup' => '<span class="discount"></span>',
    '#weight' => 1,
  ];



  $bookable_nid = \Drupal::config('abookings.bookingsettings')->get('bookable');
  $bookable = node_load($bookable_nid);
  // kint($bookable, '$bookable');

  if (! in_array("/book", $build_info_args)) {
    be_booking_form_alter($form, $form_state, $form_id);
  }

  attach_booking_data($form);
  // kint($form, '$form');
}


/**
 * Edits the booking form for the backend (administration).
 */
function be_booking_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // kint($form, '$form');

  $form['costs'] = [
    '#type' => 'fieldset',
    // '#group' => 'advanced',
    // '#title' => t('Costs'),
    'title' => ['#markup' => '<h2 class="entity-meta__title">Booking details</h2>'],
    'intro' => ['#markup' => '<p>If you change these, <em><a href="#edit-actions">save this booking</a></em> to see updated costs on the right.</p>'],
    '#open' => TRUE,
    '#weight' => -100,
    // '#optional' => FALSE,
  ];

  $form['field_reminder_sent_deposit']['#access'] = FALSE;

  $form['field_bookable_unit']['#group']  = 'costs';
  $form['field_num_guests']['#group']     = 'costs';
  $form['field_num_nights']['#group']     = 'costs';
  $form['field_checkin_date']['#group']   = 'costs';
  $form['field_checkout_date']['#group']  = 'costs';
  $form['field_addons']['#group']         = 'costs';
  $form['field_base_cost']['#group']      = 'costs';

  $booking = $form_state->getFormObject()->getEntity();
  // kint($booking, '$booking');

  $form['history'] = [
    '#type' => 'container',
    '#group' => 'advanced',
    'emails' => [
      '#markup' => get_history_as_markup($booking),
    ],
    '#attributes' => [
      'class' => ['entity-meta__header'],
    ],
    '#weight' => 100,
  ];

  $form['costs_line_items'] = [
    '#type' => 'fieldset',
    // '#group' => 'costs',
    'items_table' => [
      '#markup' => build_costs_markup($booking),
    ],
    '#weight' => -95,
  ];

  $form['status_email_send'] = [
    '#type' => 'checkbox',
    '#return_value' => 'send',
    '#title' => t('Send an email if status has been updated'),
    '#default_value' => 'send',
    '#weight' => 50,
  ];

  $booking_costs = get_booking_costs($booking);
  // kint($booking_costs, '$booking_costs');

  $form['#attached']['library'][] = 'abookings/booking_form';

  // kint($form, '$form');
}


/**
 * Edits the booking form for the frontend (Book page).
 */
function fe_booking_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // kint($form, '$form');

  $build_info_args = $form_state->getBuildInfo()['args'];

  // $form['title']['#access'] = FALSE;

  if (in_array('/book', $build_info_args)) {
    // kint($form, '$form');

    // $form['field_checkin_date']['#attributes']['class'][] = 'hidden';
    // $form['field_checkout_date']['#attributes']['class'][] = 'hidden';
    $form['field_base_cost']['#attributes']['class'][] = 'hidden';
    $form['field_num_nights']['#attributes']['class'][] = 'hidden';

    $form['calendar'] = [
      '#prefix' => '<div class="">',
      '#suffix' => '</div>',
      '#type' => 'markup',
      'calendar' => [
        '#markup' => '<div id="calendar"></div>',
      ],
      '#weight' => 0,
    ];

    $form['field_bookable_unit']['#weight'] = -50;
    $form['calendar']['#weight'] =            -49;
    $form['field_checkin_date']['#weight'] =  -48;
    $form['field_checkout_date']['#weight'] = -47;
    // kint($form, '$form');

    $form_items_to_deaccess = [
      'title',
      'field_booking_status',
      'field_paid_status',
      'field_reminder_sent_deposit',
      // 'field_history', // Hidden from form above
      'path',
      'author',
      'advanced',
      'options',
    ];
    foreach ($form_items_to_deaccess as $name) {
      $form[$name]['#access'] = FALSE;
    }

    $form_items_to_keep = [
      'field_bookable_unit',
      'field_num_guests',
      'field_num_nights',
      'field_checkin_date',
      'field_checkout_date',
      'field_addons',
      'field_promo_code_provided',
      'field_last_name',
      'field_first_name',
      'field_phone_number',
      'field_phone_number_alt',
      'field_email_address',
      'field_country',
      'field_notes',
      'field_amount_due',
      'field_base_cost',
    ];
    foreach (array_keys($form) as $key) {
      // kint($key, '$key');
      $is_field = (substr($key, 0, 6) === 'field_') ? TRUE : FALSE;
      // kint($is_field, '$is_field');
      if (! $is_field) {
        continue;
      }

      if (! in_array($key, $form_items_to_keep)) {
        // kint($key, 'Removing access to: $key');
        $form[$key]['#access'] = FALSE;
      }
    }
    // kint($form, '$form');

    $form_items_to_hide = [
      'field_amount_due',
      'field_base_cost',
    ];
    foreach ($form_items_to_hide as $name) {
      $form[$name]['#attributes']['class'][] =
        'hidden';
    }

    // Unneeded actions
    $form['status']['#access'] = FALSE;
    $form['actions']['unpublish']['#access'] = FALSE;
    $form['actions']['preview']['#access'] = FALSE;
    $form['actions']['delete']['#access'] = FALSE;
    unset($form['actions']['publish']['#dropbutton']);
  }
  // kint($form, '$form');
}



function line_item_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  // kint($form, '$form');
  // kint($form_state, '$form_state');
  $booking = \Drupal::routeMatch()->getParameter('node');
  // kint($booking, '$booking');

  $form['field_booking']['widget'][0]['target_id']['#default_value'] =
    $booking;

  $form_state->setRebuild();
  $form_state->disableRedirect();

  // Unneeded node settings
  $form['revision_information']['#access'] = FALSE;
  $form['path']['#access'] = FALSE;
  $form['author']['#access'] = FALSE;
  $form['advanced']['#access'] = FALSE;
  $form['options']['#access'] = FALSE;
  $form['meta']['#access'] = FALSE;

  // Unneeded actions
  $form['actions']['unpublish']['#access'] = FALSE;
  $form['actions']['preview']['#access'] = FALSE;
  $form['actions']['delete']['#access'] = FALSE;
  unset($form['actions']['publish']['#dropbutton']);
}
