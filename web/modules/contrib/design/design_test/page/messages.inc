<?php

/**
 * @file
 * System messages.
 */

/**
 * Displays system messages for testing.
 */
function design_test_page_messages() {
  // Error message.
  drupal_set_message(design_test_create_greeking(5) . '<a href="#">Vestibulum sed magna</a>. ' . design_test_create_greeking(14), 'error');

  // Status message.
  drupal_set_message(design_test_create_greeking(3) . '<a href="#">Vestibulum sed magna</a>. ' . design_test_create_greeking(4), 'status');

  // Warning message.
  drupal_set_message(design_test_create_greeking(8) . '<a href="#">Vestibulum sed magna</a>. ' . design_test_create_greeking(20), 'warning');

  $page['messages'] = array(
    '#markup' => '<p>' . t('This page displays system messages. If you don\'t see any messages, make sure you have $messages printing in page.tpl.php') . '</p>',
  );

  // Multiple messages.
  $page['multiple_heading'] = array(
    '#type' => 'html_tag',
    '#tag' => 'h2',
    '#value' => 'Multiple messages',
  );
  $page['multiple'] = array(
    '#theme_wrappers' => array('status_messages'),
  );
  $page['multiple'][] = array(
    '#pre_render' => array('design_test_page_pre_render_message'),
    '#message_type' => 'status',
    '#message' => design_test_create_greeking(13),
  );
  $page['multiple'][] = array(
    '#pre_render' => array('design_test_page_pre_render_message'),
    '#message_type' => 'status',
    '#message' => design_test_create_greeking(13),
  );
  $page['multiple'][] = array(
    '#pre_render' => array('design_test_page_pre_render_message'),
    '#message_type' => 'error',
    '#message' => design_test_create_greeking(13),
  );
  $page['multiple'][] = array(
    '#pre_render' => array('design_test_page_pre_render_message'),
    '#message_type' => 'error',
    '#message' => design_test_create_greeking(13),
  );
  $page['multiple'][] = array(
    '#pre_render' => array('design_test_page_pre_render_message'),
    '#message_type' => 'warning',
    '#message' => design_test_create_greeking(13),
  );
  $page['multiple'][] = array(
    '#pre_render' => array('design_test_page_pre_render_message'),
    '#message_type' => 'warning',
    '#message' => design_test_create_greeking(13),
  );

  // Special markup in messages.
  $page['special_heading'] = array(
    '#type' => 'html_tag',
    '#tag' => 'h2',
    '#value' => 'Special markup',
  );
  $page['special'] = array(
    '#theme' => 'status_messages',
    '#pre_render' => array('design_test_page_pre_render_message'),
    '#message_type' => 'status',
    '#message' => '<p>' . design_test_create_greeking(13) . '<a href="#">Vestibulum sed magna</a>. ' . design_test_create_greeking(28) . '</p>
    <ul>
      <li>' . design_test_create_greeking(6) . '</li>
      <li>' . design_test_create_greeking(5) . '</li>
      <li>' . design_test_create_greeking(3) . '</li>
    </ul>
    <p>' . design_test_create_greeking(28) . '</p>',
  );

  return $page;
}

/**
 * #pre_render callback to render messages inline.
 */
function design_test_page_pre_render_message($element) {
  drupal_set_message($element['#message'], $element['#message_type']);
  return $element;
}

