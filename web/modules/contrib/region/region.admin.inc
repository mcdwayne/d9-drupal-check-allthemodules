<?php

/**
 * @file
 * Administration functions to maintain a common set of regions for layouts.
 */

use Drupal\region\Region;

/**
 * Page callback: Presents list of regions.
 *
 * @see region_menu()
 */
function region_page_list() {
  $controller = entity_list_controller('region');
  return $controller->render();
}

/**
 * Page callback: Presents the region editing form.
 *
 * @see region_menu()
 */
function region_page_edit(Region $region) {
  drupal_set_title(t('<em>Edit region</em> @label', array('@label' => $region->label())), PASS_THROUGH);
  return entity_get_form($region);
}

/**
 * Page callback: Provides the new region addition form.
 *
 * @see region_menu()
 */
function region_page_add() {
  $region = entity_create('region', array());
  return entity_get_form($region);
}

/**
 * Page callback: Form constructor for region deletion confirmation form.
 *
 * @see region_menu()
 */
function region_delete_confirm($form, &$form_state, Region $region) {
  // Always provide entity id in the same form key as in the entity edit form.
  $form['id'] = array('#type' => 'value', '#value' => $region->id());
  $form_state['region'] = $region;
  return confirm_form($form,
    t('Are you sure you want to remove the region %title?', array('%title' => $region->label())),
    'admin/structure/regions',
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Form submission handler for region_delete_confirm().
 */
function region_delete_confirm_submit($form, &$form_state) {
  $region = $form_state['region'];
  $region->delete();
  drupal_set_message(t('Region %label has been deleted.', array('%label' => $region->label())));
  watchdog('region', 'Region %label has been deleted.', array('%label' => $region->label()), WATCHDOG_NOTICE);
  $form_state['redirect'] = 'admin/structure/regions';
}
