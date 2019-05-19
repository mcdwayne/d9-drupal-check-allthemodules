<?php

$plugin = array(
  'title' => t('Sweepstakes: User has registered'),
  'description' => t('Controls access by whether the user has registered in the sweepstake'),
  'callback' => 'sweepstake_user_has_registered_ctools_access_check',
  'summary' => 'sweepstake_user_has_registered_ctools_summary',
  'required context' => array(
    new ctools_context_required(t('User'), 'user'),
    new ctools_context_required(t('Node'), 'node'),
  ),
);

/**
 * Provide a summary description.
 */
function sweepstake_user_has_registered_ctools_summary($conf, $context) {
  return t('The user has registered in the sweepstake');
}

function sweepstake_user_has_registered_ctools_access_check($conf, $context) {
  //sanity
  if ($context[0]->type[2] != 'user' or $context[1]->type[2] != 'node') {
    return FALSE;
  }

  //if the user is anonymous, we assume he hasn't registered.
  if ($context[0]->data->uid == 0) {
    return FALSE;
  }

  return db_select('sweepstakes_entries')
    ->fields(NULL, array('seid'))
    ->condition('nid', $context[1]->data->nid)
    ->condition('uid', $context[0]->data->uid)
    ->execute()
    ->rowCount();
}
