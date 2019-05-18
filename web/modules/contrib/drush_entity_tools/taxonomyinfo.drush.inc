<?php

/**
 * Implements hook_drush_command().
 *
 * Register the typeinfo command(s)
 */
function taxonomyinfo_drush_command() {
  $commands = array();

  $commands['taxonomyinfo-vocab-list'] = array(
    'callback' => 'drush_taxonomyinfo_vocab',
    'description' => 'Show vocabularies',
    'aliases' => array('tvl'),
    'examples' => array(
      'drush taxonomyinfo-vocab-list' => 'List vocabularies',
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
  );

  $commands['taxonomyinfo-term-list'] = array(
    'callback' => 'drush_taxonomyinfo_terms',
    'description' => 'Show terms in a vocabulary',
    'aliases' => array('ttl'),
    'examples' => array(
      'drush taxonomyinfo-term-list [vocabualary name]' => 'List all terms for a vocabulary',
    ),
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_FULL,
  );

  return $commands;
}

/**
 * Check if this is machine name
 */
function drush_taxonomyinfo_is_machine_name($vocab) {
  return is_string($vocab) && !is_numeric($vocab);
}

/**
 * Log the vocabulary info
 *
 * @see drush_taxonomyinfo_vocab
 */
function drush_taxonomyinfo_process_vocab($vocab) {
  return [$vocab, $vocab->label()];
}

/**
 * List fields in an entity/bundle
 *
 * @param mixed $vocab
 *   The vocabulary name or vid.
 */
function drush_taxonomyinfo_terms($vocab = NULL) {
  if (!$vocab) {
    drush_log(dt('Specify a vocabulary'), 'error');
    return FALSE;
  }
  $terms = \Drupal::entityQuery('taxonomy_term')
    ->condition('vid', $vocab)
    ->execute();
  if (empty($terms)) {
    drush_log(dt('No terms in this vocabulary'), 'ok');
    return;
  }
  foreach (array_keys($terms) as $term) {
    $this_term = taxonomy_term_load($term);
    $leading_spaces = '';
    // $leading_spaces = str_repeat('  ', $term->depth);
    drush_log($leading_spaces . '- [' . $this_term->id() . '] ' . $this_term->label(), 'ok');
  }
}

/**
 * List all vocabularies
 */
function drush_taxonomyinfo_vocab() {
  $vocabs = entity_get_bundles('taxonomy_term');
  $vocab_info = [];
  foreach ($vocabs as $machine_name => $info) {
    $vocab_info[] = [$machine_name, $info['label']];
  }
  usort($vocab_info, function ($a, $b) {
    return strcmp($a[0], $b[0]);
  });
  return drush_print_table($vocab_info);
}
