<?php
/**
 * @file
 * Admin pages callbacks.
 */

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Admin page callback
 */
function mail_headers_config() {
  $content[] = drupal_get_form('mail_headers_admin_form');
  return $content;
}

/**
 * Admin page callback
 */
function mail_headers_headers() {
  $content[] = drupal_get_form('mail_headers_headers_form');
  return $content;
}

/**
 * Admin form
 */
function mail_headers_admin_form($form, &$form_state) {
  config_context_enter('config.context.free');

  $settings = config('mail_headers.settings');
  $form['mail_headers_use_default'] = array(
    '#default_value' => $settings->get('use_default'),
    '#type' => 'checkbox',
    '#title' => t('Allways use default address.'),
    '#description' => t('Allways use default address "@address" as "from" for all e-mails.', array('@address' => config('system.site')->get('mail'))),
  );

  $form['mail_headers_domains'] = array(
    '#type' => 'textarea',
    '#default_value' => $settings->get('domains'),
    '#title' => t('Allowed Domains'),
    '#description' => t('List of domains that are allowed to be used as sender "from" in e-mails sent by Drupal. Put one domain per line, do not include the @. If none is supplied, no e-mails will be modified.'),
    '#states' => array(
      // Hide the settings when the use default checkbox is checked.
      'invisible' => array(
        'input[name="mail_headers_use_default"]' => array('checked' => TRUE),
      ),
    ),
  );

  $form['mail_headers_html'] = array(
    '#default_value' => $settings->get('html'),
    '#type' => 'checkbox',
    '#title' => t('Allow HTML.'),
    '#description' => t('If checked, the body of the e-mail will allow html code. If unchecked, the body will be converted to plain text.'),
  );

  return system_config_form($form, $form_state);
}

/**
 * Submit handler for mail_headers_admin_form().
 */
function mail_headers_admin_form_submit(&$form, &$form_state) {
  config_context_enter('config.context.free');
  $settings = config('mail_headers.settings');
  $settings->set('use_default', $form_state['values']['mail_headers_use_default']);
  $settings->set('domains', $form_state['values']['mail_headers_domains']);
  $settings->set('html', $form_state['values']['mail_headers_html']);
  $settings->save();
}

function mail_headers_headers_form($form, &$form_state) {
  config_context_enter('config.context.free');
  $settings = config('mail_headers.settings');

  $id = drupal_html_id('mail-headers-headers');
  $items = $settings->get('headers');

  $i = 0;
  $total = count($items);

  if ($total < 1) {
    $total = 1;
  }

  if (isset($form_state['triggering_element']) && $form_state['triggering_element']['#name'] == 'headers[quantity]') {
    $total = $form_state['triggering_element']['#value'];
  }

  $form['headers'] = array(
    '#prefix' => '<div id="' . $id . '">',
    '#suffix' => '</div>',
    '#tree' => TRUE,
  );

  $form['headers']['quantity'] = array(
    '#title' => t('Number of Headers'),
    '#type' => 'textfield',
    '#description' => t('Choose how many headers you want to save.'),
    '#step' => 1,
    '#element_validate' => array('form_validate_number'),
    '#ajax' => array(
      'wrapper' => $id,
      'callback' => 'mail_headers_headers_form_ajax'
    ),
    '#limit_validation_errors' => array(),
    '#default_value' => $total,
  );

  $form['headers']['items'] = array(
    '#type' => 'tableform',
    '#tf_header' => array(
      t('Header Name'),
      t('Header Value'),
      t('Delete'),
    ),
    '#tf_rows' => array(),
  );

  while ($i < $total) {
    $hn = array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$i]['name']) ? $items[$i]['name'] : NULL,
      '#autocomplete_path' => 'mail_headers/autocomplete',
    );
    $hv = array(
      '#type' => 'textfield',
      '#default_value' => isset($items[$i]['value']) ? $items[$i]['value'] : NULL,
    );
    $delete = array(
      '#type' => 'checkbox',
      '#default_value' => NULL,
    );

    $form['headers']['items']['#tf_rows'][] = array(
      $i . '_name' => $hn,
      $i . '_value' => $hv,
      $i . '_delete' => $delete,
    );

    ++$i;
  }

  $form['tokens'] = array(
    '#theme' => 'token_tree',
    '#token_types' => array(),
  );

  return system_config_form($form, $form_state);
}

/**
 * Ajax handler for mail_headers_headers_form().
 */
function mail_headers_headers_form_ajax($form, $form_state) {
  // Lets include messages.
  $headers['messages'] = array(
    '#theme' => 'status_messages'
  );
  $headers += $form['headers'];
  return $headers;
}

/**
 * Submit handler for mail_headers_headers_form().
 */
function mail_headers_headers_form_validate(&$form, &$form_state) {
  $items = $form_state['values']['headers']['items'];
  $total = count($items) / 3;
  $i = 0;

    $forbidden = array(
      'From',
      'Reply-To',
      'X-Drupal-From'
    );

  while ($i < $total) {
    if ($items[$i . '_delete'] || !$items[$i . '_name'] && !$items[$i . '_value']) {
      // Ignore when both are empty, or when deleting.
    }
    elseif ($items[$i . '_name'] && preg_grep("/^" . $items[$i . '_name'] . "$/i" , $forbidden)) {
      form_set_error('headers][items][' . $i . '_name', t('Sorry, you are not allowed to modify the %header header.', array('%header' => $items[$i . '_name'])));
    }
    elseif (!$items[$i . '_name']) {
      form_set_error('headers][items][' . $i . '_name', t('Header Name is required.'));
    }
    elseif (!$items[$i . '_value']) {
      form_set_error('headers][items][' . $i . '_value', t('Header Value is required.'));
    }

    ++$i;
  }
}

/**
 * Submit handler for mail_headers_headers_form().
 */
function mail_headers_headers_form_submit(&$form, &$form_state) {
  config_context_enter('config.context.free');
  $settings = config('mail_headers.settings');

  $id = drupal_html_id('mail-headers-headers');

  $items = $form_state['values']['headers']['items'];
  $total = count($items) / 3;
  $i = 0;

  $headers = array();

  while ($i < $total) {
    if (!$items[$i . '_delete'] && $items[$i . '_name'] && $items[$i . '_value']) {
      $headers[] = array(
        'name' => $items[$i . '_name'],
        'value' => $items[$i . '_value'],
      );
    }
    ++$i;
  }

  $settings->set('headers', $headers);
  $settings->save();
}

/**
 * Autocomplete for recommended headers.
 *
 * TODO: Improve this list, and make it really autocomplete!
 */
function mail_headers_autocomplete($s) {
  $matches = array(
    'Content-Language' => 'Content-Language',
    'In-reply-to' => 'In-reply-to',
    'Mail-followup-to' => 'Mail-followup-to',
    'References' => 'References',
    'X-Originating-IP' => 'X-Originating-IP',
    'X-Mailer' => 'X-Mailer',
  );

return new JsonResponse($matches);
}
