<?php

/**
 * Wraps cloudwords_translation_node_overview() form into a callback page so the menu system doesn't need call back argument alterations.
 */
function cloudwords_translation_node_overview_page($node) {
  $form = \Drupal::formBuilder()->getForm('cloudwords_translation_node_overview', $node);
  return \Drupal::service("renderer")->render($form);
}


/**
 * @file
 * Pages for cloudwords_translation.module.
 */

/**
 * Wraps translation_node_overview() adding a tableselct.
 */
function cloudwords_translation_node_overview($form, &$form_state, $node) {
  $form_state['node'] = $node;

  if ($node->tnid) {
    // Already part of a set, grab that set.
    $tnid = $node->tnid;
    $translations = translation_node_get_translations($node->tnid);
  }
  else {
    // We have no translation source nid, this could be a new set, emulate that.
    $tnid = $node->nid;
    $translations = [entity_language('node', $node) => $node];
  }

  $form_state['tnid'] = $tnid;
  $queue = cloudwords_get_translatables_by_property([
    'type' => 'node_content',
    'objectid' => $tnid,
  ], 'language');

  // Pre-load nodes. Without this, the number of database queries grows linearly
  // with the number of languages. Even though translation_node_overview() calls
  // node_load(), this helps since the loaded nodes are statically cached.
  $nids = [];
  foreach ($translations as $translation) {
    $nids[] = $translation->nid;
  }
  if ($nids) {
    $node_set = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($nids);
  }
  else {
    $node_set = [];
  }

  $primary_node = FALSE;
  foreach ($node_set as $node_set_item) {
    if (empty($node_set_item->tnid) || $node_set_item->tnid == $node_set_item->nid) {
      $primary_node = $node_set_item;
      break;
    }
  }

  // Return the normal table if the user does not have access.
  if (!\Drupal::currentUser()->hasPermission('add to cloudwords queue')) {
    module_load_include('inc', 'translation', 'translation.pages');
    return translation_node_overview($node);
  }

  $form['update'] = [
    '#type' => 'fieldset',
    '#title' => t('Mark for Cloudwords Translation'),
    '#attributes' => ['class' => ['container-inline']],
  ];

  $form['update']['operation'] = [
    '#type' => 'select',
    '#options' => [
      'add' => t('Mark'),
      'remove' => t('Unmark'),
    ],
    '#default_value' => 'add',
    '#title_display' => 'invisible',
    '#title' => t('Queue operations'),
  ];

  $form['update']['actions'] = ['#type' => 'actions'];
  $form['update']['actions']['submit'] = [
    '#type' => 'submit',
    '#value' => t('Update'),
  ];

  // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// $type = variable_get('translation_language_type', LANGUAGE_TYPE_INTERFACE);


  $form['table'] = [
    '#type' => 'tableselect',
    '#header' => [
      'label' => t('Language'),
      'title' => t('Title'),
      'status' => t('Status'),
      'cloudwords' => t('Marked for Cloudwords'),
      'operations' => t('Operations'),
    ],
    '#options' => [],
    '#atributes' => [],
  ];

  $table =& $form['table'];
  $rows =& $table['#options'];

  foreach (cloudwords_language_list() as $langcode => $language) {
    $options = [];
    $language_name = $language->name;

    if (!isset($queue[$langcode]) && (!isset($translations[$langcode]) || $tnid != $translations[$langcode]->nid)) {

      if (isset($translations[$langcode])) {
        $label = $translations[$langcode]->title;
      }
      else {
        $label = $primary_node->title;
      }

      $translatable = cloudwords_translatable_create([
        'objectid' => $tnid,
        'language' => $langcode,
        'label' => $label,
        'textgroup' => 'node',
        'type' => 'node_content',
        'status' => CLOUDWORDS_QUEUE_NOT_IN_QUEUE,
      ]);
      $translatable->save();
      $queue[$langcode] = $translatable;
    }

    if (isset($translations[$langcode])) {
      // Existing translation in the translation set: display status.
      // We load the full node to check whether the user can edit it.
      $translation_node = $node_set[$translations[$langcode]->nid];
      $path = 'node/' . $translation_node->nid;
      $links = language_negotiation_get_switch_links($type, $path);
      // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $title = empty($links->links[$langcode]['href']) ? l($translation_node->title, $path) : l($translation_node->title, $links->links[$langcode]['href'], $links->links[$langcode]);

      if (node_access('update', $translation_node)) {
        $text = t('edit');
        $path = 'node/' . $translation_node->nid . '/edit';
        $links = language_negotiation_get_switch_links($type, $path);
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $options[] = empty($links->links[$langcode]['href']) ? l($text, $path) : l($text, $links->links[$langcode]['href'], $links->links[$langcode]);

      }
      $status = $translation_node->status ? t('Published') : t('Not published');
      if ($translation_node->translate) {
        $status .= ' - <span class="marker">' . t('outdated') . '</span>';
      }
      else {
        // $form['table'][$langcode]['#disabled'] = TRUE;
      }

      if ($translation_node->nid == $tnid) {
        $language_name = t('<strong>@language_name</strong> (source)', ['@language_name' => $language_name]);
        $form['table'][$langcode]['#disabled'] = TRUE;
      }
    }
    else {
      // No such translation in the set yet: help user to create it.
      $title = t('n/a');
      if (node_access('create', $node)) {
        $text = t('add translation');
        $path = 'node/add/' . str_replace('_', '-', $node->type);
        $links = language_negotiation_get_switch_links($type, $path);
        $query = ['query' => ['translation' => $node->nid, 'target' => $langcode]];
        // @FIXME
// l() expects a Url object, created from a route name or external URI.
// $options[] = empty($links->links[$langcode]['href']) ? l($text, $path, $query) : l($text, $links->links[$langcode]['href'], array_merge_recursive($links->links[$langcode], $query));

      }
      $status = t('Not translated');
    }

    $cloudwords = '';
    if (isset($queue[$langcode])) {
      $cloudwords = cloudwords_status_options_list($queue[$langcode]->status);
      // $status .= ' - <span class="marker">' . t($text) . '</span>';
    }

    $rows[$langcode] = [
      'label' => $language_name,
      'title' => $title,
      'status' => $status,
      'cloudwords' => $cloudwords,
      'operations' => implode(' | ', $options),
    ];
  }

  $form_state['cloudwords_queue'] = $queue;

  // @FIXME
// drupal_set_title() has been removed. There are now a few ways to set the title
// dynamically, depending on the situation.
//
//
// @see https://www.drupal.org/node/2067859
// drupal_set_title(t('Translations of %title', array('%title' => $node->title)), PASS_THROUGH);


  return $form;
}

/**
 * Validate callback for cloudwords_translation_node_overview().
 */
function cloudwords_translation_node_overview_validate($form, &$form_state) {

}

/**
 * Submit callback for cloudwords_translation_node_overview().
 */
function cloudwords_translation_node_overview_submit($form, &$form_state) {
  $table = array_filter($form_state['values']['table']);

  switch ($form_state['values']['operation']) {
    case 'add':
      $translations = translation_node_get_translations($form_state['tnid']);

      foreach ($table as $langcode) {
        $translatable = $form_state['cloudwords_queue'][$langcode];
        if ($translatable->status == CLOUDWORDS_QUEUE_NOT_IN_QUEUE) {
          $translatable->queue();
          drupal_set_message(t('Marked %label for translation.', ['%label' => $translatable->label]), 'status', FALSE);
        }
      }
      break;

    case 'remove':
      foreach ($table as $langcode) {
        $translatable = $form_state['cloudwords_queue'][$langcode];
        if ($translatable->status == CLOUDWORDS_QUEUE_QUEUED) {
          $translatable->dequeue();
          drupal_set_message(t('Unarked %label for translation.', ['%label' => $translatable->label]), 'status', FALSE);
        }
      }
      break;
  }
}
