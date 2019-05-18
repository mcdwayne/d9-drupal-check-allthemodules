<?php

/**
 * Configuration settings for the importer.
 * @param $form
 * @param $form_state
 * @return mixed
 */
function drealty_import_admin_settings_form($form, &$form_state) {
  $form['drealty_import_modified_span'] = array(
    '#type' => 'select',
    '#title' => 'Modified Timestamp Difference',
    '#description' => 'When querying for listing updates, what should the date threshold be to query for? Smaller values will yield fewer results. Ideally, this value should be set to 3 days.',
    '#options' => array(
      '1' => '1 day',
      '3' => '3 days',
      '5' => '5 days',
      '7' => '1 week',
      '14' => '2 weeks',
      '30' => '1 month',
      '90' => '3 months',
      '180' => '6 months',
      '365' => '1 year',
      '730' => '2 years',
      '0' => 'Any time',
    ),
    '#default_value' => variable_get('drealty_import_modified_span', 3),
  );

  if (module_exists('drealty_reconciler')) {
    $form['drealty_reconciler_check'] = array(
      '#type' => 'select',
      '#title' => 'Reconcile check',
      '#description' => 'How much time should elapse before a listing is checked to see that it still exists in RETS? A week is usually sufficient.',
      '#options' => array(
        '1' => '1 day',
        '3' => '3 days',
        '5' => '5 days',
        '7' => '1 week',
        '14' => '2 weeks',
        '0' => 'Any Time (all listings)'
      ),
      '#default_value' => variable_get('drealty_reconciler_check', 7),
    );
    $form['drealty_reconciler_delete'] = array(
      '#type' => 'checkbox',
      '#title' => 'Delete inactive listings from the system while reconciling?',
      '#description' => 'By default, all the listings that are removed from RETS are marked as INACTIVE and ARE NOT deleted from the database.',
      '#default_value' => variable_get('drealty_reconciler_delete', 0),
    );
  }

  return system_settings_form($form);
}