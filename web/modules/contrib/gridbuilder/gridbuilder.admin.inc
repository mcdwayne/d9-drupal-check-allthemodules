<?php

/**
 * @file
 * Administration functions to maintain a common set of grids for layouts.
 */

use Drupal\gridbuilder\Grid;

/**
 * Page callback: Presents list of grids.
 *
 * @see gridbuilder_menu()
 */
function gridbuilder_page_list() {
  $controller = entity_list_controller('grid');
  return $controller->render();
}

/**
 * Page callback: Presents the grid editing form.
 *
 * @see gridbuilder_menu()
 */
function gridbuilder_page_edit(Grid $grid) {
  drupal_set_title(t('<em>Edit grid</em> @label', array('@label' => $grid->label())), PASS_THROUGH);
  return entity_get_form($grid);
}

/**
 * Page callback: Provides the new grid addition form.
 *
 * @see gridbuilder_menu()
 */
function gridbuilder_page_add() {
  $grid = entity_create('grid', array());
  return entity_get_form($grid);
}

/**
 * Page callback: Form constructor for grid deletion confirmation form.
 *
 * @see gridbuilder_menu()
 */
function gridbuilder_delete_confirm($form, &$form_state, Grid $grid) {
  // Always provide entity id in the same form key as in the entity edit form.
  $form['id'] = array('#type' => 'value', '#value' => $grid->id());
  $form_state['grid'] = $grid;
  return confirm_form($form,
    t('Are you sure you want to remove the grid %title?', array('%title' => $grid->label())),
    'admin/structure/grids',
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Form submission handler for gridbuilder_delete_confirm().
 */
function gridbuilder_delete_confirm_submit($form, &$form_state) {
  $grid = $form_state['grid'];
  $grid->delete();
  drupal_set_message(t('Grid %label has been deleted.', array('%label' => $grid->label())));
  watchdog('gridbuilder', 'Grid %label has been deleted.', array('%label' => $grid->label()), WATCHDOG_NOTICE);
  $form_state['redirect'] = 'admin/structure/grids';
}
