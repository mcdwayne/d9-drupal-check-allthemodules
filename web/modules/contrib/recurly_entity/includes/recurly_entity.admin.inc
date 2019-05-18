<?php

/**
 * @file
 * Recurly Subscription Plan editing UI.
 */

/**
 * Page callback to add a new Subscription Plan.
 *
 * This is split out from editing an existing plan as new subscription plans
 * must exist in Recurly first. In order to load this form, we must be passed in
 * that remote subscription plan so we can pull it's ID and other properties.
 *
 * @param RecurlySubscriptionPlan $subscription_plan
 *   The subscription plan object loaded from Recurly and ready to present for
 *   saving.
 *
 * @return string
 *   The HTML of the form to import the subscription plan.
 */
function recurly_subscription_plan_add(RecurlySubscriptionPlan $subscription_plan) {
  // This is a bit raw but only happens if someone manually browses to the /add
  // URL.
  $local_plan = entity_get_controller('recurly_subscription_plan')->loadByPlanCode($subscription_plan->plan_code);
  if ($local_plan) {
    drupal_set_message(t('This subscription plan has already been added. Edit the existing plan or import a different plan.'));
    $paths = $local_plan->uri();
    drupal_goto($paths['path']);
  }

  $subscription_plan->save();
  drupal_set_message(t('The subscription plan was successfully imported. Local data can be edited here.'));
  drupal_goto('admin/content/recurly_subscription_plans/manage/' . $subscription_plan->id);
}

/**
 * Form callback for the Subscription plan edit form.
 *
 * @param $subscription_plan
 *   The recurly_subscription_plan entity object to edit.
 */
function recurly_subscription_plan_form($form, &$form_state, $subscription_plan) {
  $form = [];

  $form['subscription_plan_view'] = [
    '#title' => t('Recurly data'),
    '#type' => 'fieldset',
    '#weight' => 100,
    'plan' => $subscription_plan->view('recurly_data'),
  ];

  $form_state['recurly_subscription_plan'] = $subscription_plan;
  $form_state['entity_type'] = 'recurly_subscription_plan';
  field_attach_form('recurly_subscription_plan', $subscription_plan, $form, $form_state);

  $form['actions'] = [
    '#type' => 'container',
    '#attributes' => ['class' => ['form-actions']],
    '#weight' => 400,
  ];

  $form['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => t('Save subscription plan'),
    '#submit' => ['recurly_subscription_plan_form_submit'],
  ];

  return $form;
}

/**
 * Form submit callback for the Subscription plan edit form.
 */
function recurly_subscription_plan_form_submit(&$form, &$form_state) {
  $recurly_subscription_plan = entity_ui_form_submit_build_entity($form, $form_state);
  $recurly_subscription_plan->save();

  $form_state['redirect'] = 'admin/content/recurly_subscription_plans';
}
