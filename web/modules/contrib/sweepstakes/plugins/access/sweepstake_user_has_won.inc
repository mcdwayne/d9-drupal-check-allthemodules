<?php

$plugin = array(
  'title' => t('Sweepstakes: User has won'),
  'description' => t('Controls access by whether the user has won the sweepstake'),
  'callback' => 'sweepstake_user_has_won_ctools_access_check',
  'summary' => 'sweepstake_user_has_won_ctools_summary',
  'required context' => array(
    new ctools_context_required(t('User'), 'user'),
    new ctools_context_required(t('Node'), 'node'),
  ),
);

/**
 * Provide a summary description.
 */
function sweepstake_user_has_won_ctools_summary($conf, $context) {
  return t('The user has won the sweepstake');
}

function sweepstake_user_has_won_ctools_access_check($conf, $context) {
  //sanity
  if ($context[0]->type[2] != 'user' or $context[1]->type[2] != 'node') {
    return FALSE;
  }

  //if the user is anonymous, we assume he hasn't won.
  if ($context[0]->data->uid == 0) {
    return FALSE;
  }

  return db_select('sweepstakes_entries')
    ->fields(NULL, array('seid'))
    ->condition('nid', $context[1]->data->nid)
    ->condition('uid', $context[0]->data->uid)
    ->condition('prize_id', 0, '>')
    ->execute()
    ->rowCount();
}
