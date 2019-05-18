<?php

/**
 * @file
 * Views integration for maillog module.
 */

/**
 * Implements hook_views_data().
 */
function maillog_views_data() {
  $data['maillog']['table']['group'] = t('Maillog');

  $data['maillog']['table']['base'] = array(
    'field' => 'idmaillog',
    'title' => t('Maillog'),
    'help' => t("This table contains the logged e-mails."),
    'weight' => -10,
  );

  $data['maillog']['idmaillog'] = array(
    'title' => t('Maillog_ID'),
    'help' => t('The primary key of the maillog table.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['maillog']['header_message_id'] = array(
    'title' => t('Message_ID'),
    'help' => t("The 'Message_ID' e-mail address."),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['maillog']['header_from'] = array(
    'title' => t('From'),
    'help' => t("The 'From' field of the e-mail address."),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['maillog']['header_to'] = array(
    'title' => t('To'),
    'help' => t("The 'To' field of the e-mail address."),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['maillog']['header_reply_to'] = array(
    'title' => t('Reply To'),
    'help' => t("The 'Reply-To' field of the e-mail address."),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['maillog']['header_all'] = array(
    'title' => t('Header'),
    'help' => t("The 'Header' field of the e-mail."),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['maillog']['body'] = array(
    'title' => t('Body'),
    'help' => t("The 'Body' field of the e-mail."),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['maillog']['subject'] = array(
    'title' => t('Subject'),
    'help' => t("The 'Subject' field of the e-mail."),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['maillog']['sent_date'] = array(
    'title' => t('Date'),
    'help' => t("The 'Date' field of the e-mail."),
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['maillog']['delete_maillog'] = array(
    'field' => array(
      'title' => t('Delete link'),
      'help' => t('Provide a simple link to delete an eMail entry from the maillog table.'),
      'id' => 'maillog_field_delete',
      'real field' => 'idmaillog',
    ),
  );
  return $data;
}
