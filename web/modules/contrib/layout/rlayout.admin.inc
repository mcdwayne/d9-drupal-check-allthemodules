<?php

/**
 * @file
 * Administration functions to maintain a set of layouts.
 */

use Drupal\rlayout\RLayout;

/**
 * Page callback: Presents list of layouts.
 *
 * @see layout_menu()
 */
function rlayout_page_list() {
  $controller = entity_list_controller('rlayout');
  return $controller->render();
}

/**
 * Page callback: Presents the layout editing form.
 *
 * @see layout_menu()
 */
function rlayout_page_edit(RLayout $layout) {
  drupal_set_title(t('<em>Edit layout</em> @label', array('@label' => $layout->label())), PASS_THROUGH);
  return entity_get_form($layout);
}

/**
 * Page callback: Provides the new layout addition form.
 *
 * @see layout_menu()
 */
function rlayout_page_add() {
  $layout = entity_create('rlayout', array());
  return entity_get_form($layout);
}

/**
 * Page callback: Form constructor for layout deletion confirmation form.
 *
 * @see layout_menu()
 */
function rlayout_delete_confirm($form, &$form_state, RLayout $layout) {
  // Always provide entity id in the same form key as in the entity edit form.
  $form['id'] = array('#type' => 'value', '#value' => $layout->id());
  $form_state['layout'] = $layout;
  return confirm_form($form,
    t('Are you sure you want to remove the layout %title?', array('%title' => $layout->label())),
    'admin/structure/layouts',
    t('This action cannot be undone.'),
    t('Delete'),
    t('Cancel')
  );
}

/**
 * Form submission handler for layout_delete_confirm().
 */
function rlayout_delete_confirm_submit($form, &$form_state) {
  $layout = $form_state['layout'];
  $layout->delete();
  drupal_set_message(t('Layout %label has been deleted.', array('%label' => $layout->label())));
  watchdog('layout', 'Layout %label has been deleted.', array('%label' => $layout->label()), WATCHDOG_NOTICE);
  $form_state['redirect'] = 'admin/structure/layouts';
}
