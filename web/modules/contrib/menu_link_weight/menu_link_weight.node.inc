<?php

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\system\Entity\Menu;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;

/**
 * Implements hook_form_BASE_FORM_ID_alter() for node forms.
 */
function menu_link_weight_form_node_form_alter(&$form, FormStateInterface $form_state) {
  $current_user = \Drupal::currentUser();
  $module_handler = \Drupal::moduleHandler();
  /** @var \Drupal\Core\Render\ElementInfoManagerInterface $element_info_manager */
  $element_info_manager = \Drupal::service('plugin.manager.element_info');

  $is_admin = $current_user->hasPermission('administer menu') && isset($form['field_menulink']['widget'][0]);
  $is_admin_per_menu = $module_handler->moduleExists('menu_admin_per_menu')
    && function_exists('_menu_admin_per_menu_filter_parent_options')
    && isset($form['field_menulink']['widget'][0]['parent'])
    && !$current_user->hasPermission('administer menu')
    && _menu_admin_per_menu_filter_parent_options($form['field_menulink']['widget'][0]);

  // Only allow users with the "administer menu" permission or that the Menu
  // Admin Per Menu has granted access to some menus.
  if (!$is_admin && !$is_admin_per_menu) {
    return;
  }

  // Prevent the "weight" widget from being displayed.
  $form['field_menulink']['widget'][0]['weight']['#access'] = FALSE;

  // Add submission/validation handlers.
  $form['#validate'][] = 'menu_link_weight_node_form_validate';
  /** @see menu_ui_form_node_form_alter() */
  foreach (array_keys($form['actions']) as $action) {
    if ($action != 'preview' && isset($form['actions'][$action]['#type']) && $form['actions'][$action]['#type'] === 'submit') {
      $form['actions'][$action]['#submit'][] = 'menu_link_weight_node_form_submit';
    }
  }

  // Add the Menu Link weight fieldset.
  $defaults = $element_info_manager->getInfo('fieldset');

  // Hardcode the order of the items previous to the menu link weight fieldset, so that we can give the fieldset a
  // weight of 10 to make it appear at the end of the menu <details> element.
  $form['field_menulink']['widget'][0]['enabled']['#weight'] = 0;
  $form['field_menulink']['widget'][0]['title']['#weight'] = 1;
  $form['field_menulink']['widget'][0]['description']['#weight'] = 2;
  $form['field_menulink']['widget'][0]['menu_parent']['#weight'] = 3;

  // 'menu' is the <details> element.
  $form['field_menulink']['widget'][0]['menu']['menu_link_weight'] = array(
    '#type' => 'fieldset',
    '#title' => t('Menu link weight'),
    '#prefix' => '<div id="menu-link-weight-wrapper">',
    '#suffix' => '</div>',
    '#process' => array_merge($defaults['#process'], ['menu_link_weight_node_element_process']),
    '#states' => [
      'invisible' => [
        'input[name="field_menulink[0][enabled]"]' => ['checked' => FALSE],
      ],
    ],
    '#weight' => 10,
  );
  $form['field_menulink']['widget'][0]['menu']['menu_link_weight']['table'] = array(
    '#type' => 'table',
    '#header' => array(
      'name' => t('Name'),
      'weight' => t('Weight'),
    ),
    '#id' => 'menu-link-weight-reorder',
    '#tabledrag' => array(array(
      'action' => 'order',
      'relationship' => 'sibling',
      'group' => 'menu-link-weight-item-weight',
    )),
    // Remove the 'table' element from the form value structure.
    '#parents' => ['field_menulink', 0, 'menu_link_weight'],
  );

  // Define the "db_weights" element, which will hold hidden fields with
  // the values of the menu links in the database. Upon validation we will
  // check whether the weights are still the same as when the form was
  // built, to make sure users won't overwrite each other's changes.
  $form['field_menulink']['widget'][0]['db_weights'] = array(
    '#tree' => TRUE,
    '#prefix' => '<div id="menu-link-weight-db-weights-wrapper">',
    '#suffix' => '</div>',
  );
  $form['#attached']['library'][] = 'menu_link_weight/menu_link_weight';

  // Define the AJAX callback for changes in the Parent element.
  $form['field_menulink']['widget'][0]['menu_parent']['#ajax'] = array(
    'callback' => 'menu_link_weight_node_parent_ajax_callback',
    'event' => 'change',
  );

  // This next button will not be displayed if JS is enabled.
  $form['field_menulink']['widget'][0]['menu']['menu_link_weight_nojs'] = array(
    '#type' => 'submit',
    '#value' => menu_link_weight_get_button_text(),
    // No need to validate when submitting this.
    '#limit_validation_errors' => array(),
    '#validate' => array(),
    '#submit' => array('menu_link_weight_node_form_update_parent_submit'),
    '#attributes' => ['class' => ['js-hide']],
  );
}

/**
 * Custom submit hook for node form which sets a different menu link parent.
 *
 * @see menu_link_weight_form_node_form_alter()
 * @see menu_link_weight_node_parent_ajax_callback()
 */
function menu_link_weight_node_form_update_parent_submit($form, FormStateInterface $form_state) {
  $form_state->setRebuild();
}

/**
 * AJAX callback that returns the updated menu_link_weight element.
 *
 * This will update whenever the selection of the menu link parent changes.
 */
function menu_link_weight_node_parent_ajax_callback($form, FormStateInterface $form_state) {
  $response = new AjaxResponse();
  $response->addCommand(new ReplaceCommand('#menu-link-weight-wrapper', drupal_render($form['field_menulink']['widget'][0]['menu']['menu_link_weight'])));
  $response->addCommand(new ReplaceCommand('#menu-link-weight-db-weights-wrapper', drupal_render($form['field_menulink']['widget'][0]['db_weights'])));
  return $response;

}

/**
 * Process callback for the menu link weight element.
 */
function menu_link_weight_node_element_process($element, FormStateInterface $form_state, &$complete_form) {
  // Find out which parent to select after loading (or AJAX reloading) the form.
  $parent_element = $complete_form['field_menulink']['widget'][0]['menu_parent'];
  $parent_value = _menu_link_weight_get_parent_value_from_element($parent_element, $form_state);
  if (strstr($parent_value, ':')) {
    // The parent value is of the form "$menu_name:" or
    // "$menu_name:$link_plugin_id".
    /** @see \Drupal\Core\Menu\MenuParentFormSelector::getParentSelectOptions() */
    list($menu_name, $parent_id) = explode(':', $parent_value, 2);
  }
  else {
    return $element;
  }

  // Get the menu title for the parent based on the current menu selection.
  $url = Url::fromRoute('entity.menu.edit_form', array('menu' => $menu_name));
  if ($parent_id === '') {
    $label = Menu::load($menu_name)->label();
  }
  else {
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
    $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
    /** @var \Drupal\Core\Menu\MenuLinkInterface $parent_link */
    $parent_link = $menu_link_manager->createInstance($parent_id);
    $label = $parent_link->getTitle();

    /** @see menu_link_weight_form_menu_form_alter() */
    $html_id = 'menu-link-weight-link-id-' . Html::getId($parent_id);
    $url->setOption('fragment', $html_id);
  }
  $generated_url = $url->toString(TRUE);
  $generated_url->applyTo($element);
  $element['#description'] = t('Change the weight of the links within the <a href=":url">@menu</a> menu by dragging the items up/down.', array(
    ':url' => $generated_url->getGeneratedUrl(),
    '@menu' => $label,
  ));

  // Get the ID for the current menu link.
  $current_mlid = NULL;
  $node = $form_state->getFormObject()->getEntity();

  if ($node) {
    $current_mlid =  $node->get('field_menulink')[0]->getMenuPluginId();
  }

  // Get the title for the current menu link.
  $new_item_title = $form_state->hasValue(['field_menulink', 0, 'link_title']) ? $form_state->getValue(['field_menulink', 0, 'link_title']) : $complete_form['field_menulink']['widget'][0]['title']['#default_value'];

  // Get the options array we will use to build the form elements for every
  // menu link.
  $options = _menu_link_weight_get_options($menu_name, $parent_id, $current_mlid, $new_item_title);
  // Allow other modules to reorder the options, if applicable.
  if ($relative_position = $form_state->get(['menu_link_weight_relative_position', $parent_value])) {
    $options = _menu_link_weight_reorder_options($options, $relative_position);
  }
  // Build the form elements using the options array.
  foreach ($options as $link_id => $info) {
    $element['table'][$link_id] = array(
      '#attributes' => ['class' => ['draggable']],
      '#weight' => $info['weight'],
      'name' => $info['title'],
      'weight' => array(
        '#type' => 'weight',
        '#title' => t('Weight'),
        '#default_value' => $info['weight'],
        '#delta' => MENU_LINK_WEIGHT_MAX_DELTA,
        '#title_display' => 'invisible',
        '#attributes' => ['class' => ['menu-link-weight-item-weight']],
      ),
    );
    if ($link_id != 'link_current') {
      // Save the current weight in the database of the rendered menu links
      // into the form, so that we can give an error if the weights have changed
      // by the time the form is submitted.
      $complete_form['field_menulink']['widget'][0]['db_weights'][$link_id] = array(
        '#type' => 'hidden',
        '#value' => $info['db_weight'],
      );
    }
  }
  return $element;
}

/**
 * Validation hook for the menu_link weight element.
 */
function menu_link_weight_node_form_validate($form, FormStateInterface $form_state) {
  /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
  $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

  $parent_element = $form['field_menulink']['widget'][0]['menu_parent'];
  $parent_value = _menu_link_weight_get_parent_value_from_element($parent_element, $form_state);
  if ($form_state->hasValue(['field_menulink', 0, 'menu_link_weight'])) {
    list($menu_name, $parent_id) = explode(':', $parent_value, 2);
    // Loop through submitted weights and confirm that the parent link/menu
    // are still the same.
    $weights = $form_state->getValue(['field_menulink', 0, 'menu_link_weight']);
    unset($weights['link_current']);
    foreach ($weights as $link_id => $weight) {
      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $link = $menu_link_manager->createInstance($link_id);
      if ($link->getParent() != $parent_id) {
        $form_state->setErrorByName('field_menulink][widget][0][menu_link_weight', t('The parent for one of the menu links have been changed by another user, please try again.'));
      }
      if ($link->getMenuName() != $menu_name) {
        $form_state->setErrorByName('field_menulink][widget][0][menu_link_weight', t('The menu for one of the menu links have been changed by another user, please try again.'));
      }
    }
  }

  if ($form_state->hasValue(['field_menulink', 0, 'db_weights'])) {
    // Check that children and weights are still the same as when the form was
    // loaded. Get the old values from the "hidden" form elements.
    foreach ($form_state->getValue(['field_menulink', 0, 'db_weights']) as $link_id => $db_weight) {
      $link = $menu_link_manager->createInstance($link_id);
      if ($link->getWeight() != $db_weight) {
        $form_state->setErrorByName('menu][menu_link_weight', t('The menu link weights have been changed by another user, please try again.'));
      }
    }
  }

  if (!$form_state->getErrors() && $form_state->hasValue(['field_menulink', 0, 'menu_link_weight', 'link_current', 'weight'])) {
    // Override the weight of the current link.
    $form_state->setValue(['field_menulink', 0, 'weight'], $form_state->getValue(['field_menulink', 0, 'menu_link_weight', 'link_current', 'weight']));
  }
}

/**
 * Custom submit hook for node form which reorders menu link weights.
 *
 * Note: this wil not reorder links that the current user does not have access
 * to (ie. links to access-controlled nodes).
 */
function menu_link_weight_node_form_submit($form, FormStateInterface $form_state) {
  // Return on empty submissions or if menu selection not enabled.
  if (!$form_state->hasValue(['field_menulink', 0, 'menu_link_weight'])
      || !$form_state->getValue(['field_menulink', 0,'enabled'])) {
    return;
  }

  /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager */
  $menu_link_manager = \Drupal::service('plugin.manager.menu.link');

  $connection = \Drupal::database();
  $transaction = $connection->startTransaction();
  try {
    // Because the form elements were keyed with the item ids from the database,
    // we can simply iterate through the submitted values.
    foreach ($form_state->getValue(['field_menulink', 0, 'menu_link_weight']) as $link_id => $info) {
      if ($link_id == 'link_current') {
        // Do nothing. Changing the weight of the current link will be handled
        // by menu_ui_form_node_form_submit() instead.
        continue;
      }

      /** @var \Drupal\Core\Menu\MenuLinkInterface $link */
      $menu_link_manager->updateDefinition($link_id, ['weight' => $info['weight']]);
    }
  }
  catch (Exception $e) {
    $transaction->rollback();
    watchdog_exception('menu_link_weight', $e);
  }
}
