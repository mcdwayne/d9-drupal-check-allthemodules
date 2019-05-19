<?php

$plugin = array(
  'title' => t('Sweepstakes: Prizes are drawn'),
  'description' => t('Controls access by whether the sweepstake prizes have been drawn'),
  'callback' => 'sweepstake_prizes_are_drawn_ctools_access_check',
  'summary' => 'sweepstake_prizes_are_drawn_ctools_summary',
  'required context' => new ctools_context_required(t('Node'), 'node'),
);

/**
 * Provide a summary description.
 */
function sweepstake_prizes_are_drawn_ctools_summary($conf, $context) {
  return t('The sweepstakes prizes have been drawn');
}

function sweepstake_prizes_are_drawn_ctools_access_check($conf, $context) {
  return db_select('sweepstakes_entries')
    ->fields(NULL, array('seid'))
    ->condition('nid', $context->data->nid)
    ->condition('prize_id', 0, '>')
    ->execute()
    ->rowCount();
}
