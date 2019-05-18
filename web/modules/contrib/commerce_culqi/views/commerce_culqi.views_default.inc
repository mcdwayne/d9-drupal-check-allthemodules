<?php

/**
 * Views for the commerce culqui.
 */

/**
 * Implements hook_views_default_views().
 */
function commerce_culqui_views_default_views() {
  $views = array();

  $view = new view();
  $view->name = 'commerce_culqui';
  $view->description = '';
  $view->tag = 'default';
  $view->base_table = 'commerce_culqui';
  $view->human_name = 'Commerce Culqui';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Commerce Culqui';
  $handler->display->display_options['use_more_always'] = FALSE;
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'access site reports';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '10';
  $handler->display->display_options['style_plugin'] = 'table';
  /* Field: Commerce Culqui: Id */
  $handler->display->display_options['fields']['py_id']['id'] = 'py_id';
  $handler->display->display_options['fields']['py_id']['table'] = 'commerce_culqui';
  $handler->display->display_options['fields']['py_id']['field'] = 'py_id';
  $handler->display->display_options['fields']['py_id']['alter']['make_link'] = TRUE;
  $handler->display->display_options['fields']['py_id']['alter']['path'] = 'admin/commerce_culqui/[py_id]/view';
  /* Field: Commerce Culqui: Date created */
  $handler->display->display_options['fields']['py_created']['id'] = 'py_created';
  $handler->display->display_options['fields']['py_created']['table'] = 'commerce_culqui';
  $handler->display->display_options['fields']['py_created']['field'] = 'py_created';
  $handler->display->display_options['fields']['py_created']['label'] = 'Date';
  $handler->display->display_options['fields']['py_created']['date_format'] = 'short';
  $handler->display->display_options['fields']['py_created']['second_date_format'] = 'long';
  /* Field: Commerce Culqui: Order order_id */
  $handler->display->display_options['fields']['py_order_id']['id'] = 'py_order_id';
  $handler->display->display_options['fields']['py_order_id']['table'] = 'commerce_culqui';
  $handler->display->display_options['fields']['py_order_id']['field'] = 'py_order_id';
  $handler->display->display_options['fields']['py_order_id']['label'] = 'Order';
  $handler->display->display_options['fields']['py_order_id']['alter']['make_link'] = TRUE;
  $handler->display->display_options['fields']['py_order_id']['alter']['path'] = 'admin/commerce/orders/[py_order_id]/view';
  /* Field: Commerce Culqui: State transaction */
  $handler->display->display_options['fields']['py_state_transaction']['id'] = 'py_state_transaction';
  $handler->display->display_options['fields']['py_state_transaction']['table'] = 'commerce_culqui';
  $handler->display->display_options['fields']['py_state_transaction']['field'] = 'py_state_transaction';
  $handler->display->display_options['fields']['py_state_transaction']['label'] = 'State';
  /* Field: Commerce Culqui: Reference Culqui */
  $handler->display->display_options['fields']['py_reference_culqui']['id'] = 'py_reference_culqui';
  $handler->display->display_options['fields']['py_reference_culqui']['table'] = 'commerce_culqui';
  $handler->display->display_options['fields']['py_reference_culqui']['field'] = 'py_reference_culqui';
  $handler->display->display_options['fields']['py_reference_culqui']['separator'] = '';
  /* Field: Commerce Culqui: Value */
  $handler->display->display_options['fields']['value']['id'] = 'value';
  $handler->display->display_options['fields']['value']['table'] = 'commerce_culqui';
  $handler->display->display_options['fields']['value']['field'] = 'value';
  $handler->display->display_options['fields']['value']['precision'] = '0';
  $handler->display->display_options['fields']['value']['separator'] = '.';
  /* Filter criterion: Commerce Culqui: Reference Culqui */
  $handler->display->display_options['filters']['py_reference_culqui']['id'] = 'py_reference_culqui';
  $handler->display->display_options['filters']['py_reference_culqui']['table'] = 'commerce_culqui';
  $handler->display->display_options['filters']['py_reference_culqui']['field'] = 'py_reference_culqui';
  $handler->display->display_options['filters']['py_reference_culqui']['exposed'] = TRUE;
  $handler->display->display_options['filters']['py_reference_culqui']['expose']['operator_id'] = 'py_reference_culqui_op';
  $handler->display->display_options['filters']['py_reference_culqui']['expose']['label'] = 'Reference Culqui';
  $handler->display->display_options['filters']['py_reference_culqui']['expose']['operator'] = 'py_reference_culqui_op';
  $handler->display->display_options['filters']['py_reference_culqui']['expose']['identifier'] = 'py_reference_culqui';
  $handler->display->display_options['filters']['py_reference_culqui']['expose']['remember_roles'] = array(
    2 => '2',
    1 => 0,
    3 => 0,
    4 => 0,
  );
  /* Filter criterion: Commerce Culqui: Order order_id */
  $handler->display->display_options['filters']['py_order_id']['id'] = 'py_order_id';
  $handler->display->display_options['filters']['py_order_id']['table'] = 'commerce_culqui';
  $handler->display->display_options['filters']['py_order_id']['field'] = 'py_order_id';
  $handler->display->display_options['filters']['py_order_id']['exposed'] = TRUE;
  $handler->display->display_options['filters']['py_order_id']['expose']['operator_id'] = 'py_order_id_op';
  $handler->display->display_options['filters']['py_order_id']['expose']['label'] = 'Order order_id';
  $handler->display->display_options['filters']['py_order_id']['expose']['operator'] = 'py_order_id_op';
  $handler->display->display_options['filters']['py_order_id']['expose']['identifier'] = 'py_order_id';
  $handler->display->display_options['filters']['py_order_id']['expose']['remember_roles'] = array(
    2 => '2',
    1 => 0,
    3 => 0,
    4 => 0,
  );

  /* Display: Page */
  $handler = $view->new_display('page', 'Page', 'page');
  $handler->display->display_options['path'] = 'admin/commerce_culqui/report';
  $handler->display->display_options['menu']['type'] = 'normal';
  $handler->display->display_options['menu']['title'] = 'Commerce Culqui';
  $handler->display->display_options['menu']['description'] = 'Transactions commerce Culqui';
  $handler->display->display_options['menu']['weight'] = '0';
  $handler->display->display_options['menu']['context'] = 0;
  $handler->display->display_options['menu']['context_only_inline'] = 0;
  $translatables['commerce_culqui'] = array(
    t('Master'),
    t('Commerce Culqui'),
    t('more'),
    t('Apply'),
    t('Reset'),
    t('Sort by'),
    t('Asc'),
    t('Desc'),
    t('Items per page'),
    t('- All -'),
    t('Offset'),
    t('« first'),
    t('‹ previous'),
    t('next ›'),
    t('last »'),
    t('Id'),
    t('.'),
    t(','),
    t('Date'),
    t('Order'),
    t('State'),
    t('Reference Culqui'),
    t('Value'),
    t('Order order_id'),
    t('Page'),
  );


  $views[$view->name] = $view;

  return $views;
}