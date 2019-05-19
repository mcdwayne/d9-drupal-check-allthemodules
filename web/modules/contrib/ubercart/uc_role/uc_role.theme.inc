<?php

/**
 * @file
 * Theme functions for the uc_role module.
 */

/**
 * Themes the roles dialog on the account edit page.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @return string
 *   Formatted HTML.
 *
 * @ingroup themeable
 */
function theme_uc_role_user_new(array $variables) {
  $form = $variables['form'];

  // Render the expiration tables first.
  $output = drupal_render($form['expirations']);

  $output .= '<div class="expiration">';
  $output .= drupal_render($form['new_role']);
  $output .= drupal_render($form['new_role_add']);
  $output .= drupal_render($form['new_role_add_for']);
  $output .= drupal_render($form['new_role_add_qty']);
  $output .= drupal_render($form['new_role_add_granularity']);
  $output .= '</div>';

  return $output;
}

/**
 * Themes the role expiration table on the account edit page.
 *
 * @param array $variables
 *   An associative array containing:
 *   - form: A render element representing the form.
 *
 * @return string
 *   Formatted HTML.
 *
 * @ingroup themeable
 */
function theme_uc_role_user_expiration(array $variables) {
  $form = $variables['form'];

  $header = [
    ['data' => t('Make permanent')],
    ['data' => t('Role')],
    ['data' => t('Expiration')],
    ['data' => t('Add/remove time')],
  ];

  $rows = [];
  // The expiration table.
  foreach ((array) $form['table'] as $rid => $expiration) {
    // We only want numeric rids.
    if (!is_numeric($rid)) {
      continue;
    }

    // Make sure the renders actually touch the elements.
    $data = &$form['table'][$rid];

    $rows[] = [
      ['data' => drupal_render($data['remove'])],
      ['data' => $data['name']['#value']],
      ['data' => \Drupal::service('date.formatter')->format($data['expiration']['#value'], 'short')],

      // Options to adjust the expiration.
      ['data' => '<a name="role-expiration-' . $rid . '">' .
                 '<div class="expiration">' .
                 drupal_render($data['polarity']) . drupal_render($data['qty']) . drupal_render($data['granularity']) .
                 '</div></a>'],
    ];
  }

  $table = [
    '#theme' => 'table',
    '#header' => $header,
    '#rows' => $rows,
    '#caption' => t('Below you can add or remove time to the expiration dates of the following roles.'),
    '#empty' => t('There are no pending expirations for roles this user.'),
  ];
  $output  = drupal_render($table);
  $output .= drupal_render_children($form);

  return $output;
}
