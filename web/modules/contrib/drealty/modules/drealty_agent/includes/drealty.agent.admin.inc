<?php

class DrealtyAgentUIController extends EntityDefaultUIController {

  public function hook_menu() {

    $id_count = count(explode('/', $this->path));
    $wildcard = isset($this->entityInfo['admin ui']['menu wildcard']) ? $this->entityInfo['admin ui']['menu wildcard'] : '%' . $this->entityType;

    $items[$this->path] = array(
      'title' => 'Drealty Agents',
      'description' => 'Manage Drealty Listing Content',
      'page callback' => 'drealty_agent_admin',
      'access arguments' => array('access drealty admin'),
      'type' => MENU_LOCAL_TASK | MENU_NORMAL_ITEM,
      'file' => 'drealty.agent.admin.inc',
      'file path' => drupal_get_path('module', $this->entityInfo['module']) . '/includes',
    );


      $items['drealty_agent/add'] = array(
        'title' => 'Add dRealty Listing',
        'title callback' => 'check_plain',
        'page callback' => 'drealty_agent_form_wrapper',
        'page arguments' => array('add', drealty_agent_create(array('rets_imported' => FALSE))),
        'access arguments' => array('create drealty listing'),
        'file' => 'drealty.agent.admin.inc',
        'file path' => drupal_get_path('module', $this->entityInfo['module']) . '/includes',
        'type' => MENU_NORMAL_ITEM,
      );
    

    $items['drealty_agent/' . $wildcard] = array(
      'title callback' => 'drealty_agent_page_title',
      'title arguments' => array(1),
      'page callback' => 'drealty_agent_view',
      'page arguments' => array(1),
      'access callback' => 'drealty_access',
      'access arguments' => array('view', 1),
      'type' => MENU_CALLBACK,
    );

    $items['drealty_agent/' . $wildcard . '/view'] = array(
      'title' => 'View',
      'type' => MENU_DEFAULT_LOCAL_TASK,
      'weight' => -10,
    );

    // Loading and editing model entities
    $items['drealty_agent/' . $wildcard . '/edit'] = array(
      'title' => 'Edit',
      'page callback' => 'drealty_agent_form_wrapper',
      'page arguments' => array('edit', 1),
      'access callback' => 'drealty_access',
      'access arguments' => array('edit', 1),
      'weight' => 0,
      'context' => MENU_CONTEXT_PAGE | MENU_CONTEXT_INLINE,
      'type' => MENU_LOCAL_TASK,
      'file' => 'drealty.agent.admin.inc',
      'file path' => drupal_get_path('module', $this->entityInfo['module']) . '/includes'
    );

    $items['drealty_agent/' . $wildcard . '/delete'] = array(
      'title' => 'Delete',
      'page callback' => 'drealty_agent_form_wrapper',
      'page arguments' => array('delete', 1),
      'access callback' => 'drealty_access',
      'access arguments' => array('delete', 1),
      'type' => MENU_LOCAL_TASK,
      'context' => MENU_CONTEXT_INLINE,
      'weight' => 10,
      'file' => 'drealty.agent.admin.inc',
      'file path' => drupal_get_path('module', $this->entityInfo['module']) . '/includes'
    );

    if (module_exists('devel')) {
      $items['drealty_agent/' . $wildcard . '/devel'] = array(
        'title' => 'Devel',
        'page callback' => 'devel_load_object',
        'page arguments' => array('node', 1),
        'access arguments' => array('access devel information'),
        'type' => MENU_LOCAL_TASK,
        'file' => 'devel.pages.inc',
        'file path' => drupal_get_path('module', 'devel'),
        'weight' => 100,
      );
      $items['drealty_agent/' . $wildcard . '/devel/load'] = array(
        'title' => 'Load',
        'type' => MENU_DEFAULT_LOCAL_TASK,
      );
      $items['drealty_agent/' . $wildcard . '/devel/render'] = array(
        'title' => 'Render',
        'page callback' => 'devel_render_object',
        'page arguments' => array('drealty_agent', 1),
        'access arguments' => array('access devel information'),
        'file' => 'devel.pages.inc',
        'file path' => drupal_get_path('module', 'devel'),
        'type' => MENU_LOCAL_TASK,
        'weight' => 100,
      );
    }

    $items['drealty_agent/' . $wildcard . '/rets_refresh'] = array(
      'title' => 'Update RETS Data',
      'page callback' => 'drupal_get_form',
      'page arguments' => array('drealty_update_single_listing', 1),
      'access arguments' => array('access drealty admin'),
      'type' => MENU_LOCAL_TASK,
      'context' => MENU_CONTEXT_INLINE | MENU_CONTEXT_PAGE,
      'weight' => 99,
    );

    return $items;
  }

}


/**
 * Page callback for adding listings.
 */
function drealty_agent_add_page() {
  $item = menu_get_item();
  if ($content = system_admin_menu_block($item)) {
    $output = theme('admin_block_content', array('content' => $content));
  } else {
    $output = t('You do not have any administrative items.');
  }
  return $output;
}

/**
 * Page callback form managing listings.
 */
function drealty_agent_admin($type = 'new') {
  $edit = $_POST;
  if (isset($edit['operation']) && ($edit['operation'] == 'delete') && isset($edit['listings']) && $edit['listings']) {
    return drupal_get_form('drealty_agent_multiple_delete_confirm');
  } else {
    return drupal_get_form('drealty_agent_admin_overview', $type);
  }
}

/**
 * Form builder for the listing admin overview form.
 */
function drealty_agent_admin_overview($form, &$form_state, $arg) {
  $header = array(
    'mls_id' => array('data' => t('Agent MLS Id'), 'field' => 'rets_id'),
    'title' => array('data' => t('Title'), 'field' => 'label'),
    'type' => array('data' => t('Bundle'), 'field' => 'type'),
    'updated' => array('data' => t('Changed'), 'field' => 'created'),
    'operations' => array('data' => t('Operations')),
  );

  $query = db_select('drealty_agent', 'd')->extend('PagerDefault')->extend('TableSort');
  $result = $query
    ->fields('d', array('id', 'rets_id', 'label'))
    ->limit(10)
    ->orderByHeader($header)
    ->execute();

  $lids = array();
  foreach ($result as $row) {
    $lids[] = $row->id;
  }

  $listings = entity_load('drealty_agent', $lids);

  $options = array();

  foreach ($listings as $listing) {

    $links = menu_contextual_links('drealty', 'drealty_agent', array($listing->id));

    $options[$listing->id] = array(
      'mls_id' => array('data' => array('#type' => 'link', '#title' => $listing->rets_id, '#href' => 'drealty_agent/' . $listing->id)),
      'title' => $listing->label,
      'type' => $listing->type,      
      'updated' => format_date($listing->changed, 'short'),
      'operations' => theme('links', array('links' => $links, 'attributes' => array('class' => array('links', 'inline', 'operations')))),
    );
  }
  $form['listings'] = array(
    '#type' => 'tableselect',
    '#header' => $header,
    '#options' => $options,
    '#empty' => t('No listings available.'),
  );

  $form['pager'] = array('#theme' => 'pager');
  return $form;
}

/**
 * Utility function to get the listing edit form.
 *
 * @param $op
 *   The operation the form should perform.
 * @param $listing
 *  The listing to perform the operation on.
 */
function drealty_agent_form_wrapper($op, $listing) {
  if ($op == 'create' || $op == 'add') {
    drupal_set_title(t('<em>@op @type</em> listing', array('@op' => ucfirst($op), '@type' => $listing->type)), PASS_THROUGH);
  } else {
    drupal_set_title(t('<em>@op @type</em> listing @title : #@rets_id', array('@op' => ucfirst($op), '@type' => $listing->type, '@title' => $listing->label, '@rets_id' => $listing->rets_key)), PASS_THROUGH);
  }
  return drupal_get_form('drealty_agent_edit_form', $op, $listing);
}

/**
 * Form builder for the listing form.
 */
function drealty_agent_edit_form($form, &$form_state, $op, $listing) {

  $form_state['listing'] = $listing;


  if ($listing->rets_imported) {
    
  }

  field_attach_form('drealty_agent', $listing, $form, $form_state);

  $form['actions'] = array(
    '#type' => 'container',
    '#attributes' => array('class' => array('form-actions')),
    '#weight' => 400,
  );

  // We add the form's #submit array to this button along with the actual submit
  // handler to preserve any submit handlers added by a form callback_wrapper.
  $submit = array();

  if (!empty($form['#submit'])) {
    $submit += $form['#submit'];
  }

  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Save listing'),
    '#submit' => $submit + array('drealty_agent_edit_form_submit'),
  );

  if (!empty($listing->name) && !$listing->rets_imported) {
    $form['actions']['delete'] = array(
      '#type' => 'submit',
      '#value' => t('Delete listing'),
      '#suffix' => l(t('Cancel'), 'admin/content/drealty_agents'),
      '#submit' => $submit + array('drealty_agent_form_submit_delete'),
      '#weight' => 45,
    );
  }

  return $form;
}

/**
 * Submit handler for the listing form.
 */
function drealty_agent_edit_form_submit($form, &$form_state) {
  // grab the listing
  $listing = $form_state['listing'];
  // attach any fields from the field api
  field_attach_submit('drealty_agent', $listing, $form, $form_state);
  // save the listing
  $listing->save();
  // redirect back to the content list
  $form_state['redirect'] = 'admin/content/drealty_agents';
}