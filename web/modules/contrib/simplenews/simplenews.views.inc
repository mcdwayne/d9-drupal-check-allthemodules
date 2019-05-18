<?php

/**
 * @file
 * Views interface for simplenews.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 */
function simplenews_field_views_data(FieldStorageConfigInterface $field_storage) {
  $data = views_field_default_views_data($field_storage);
  foreach ($data as $table_name => $table_data) {
    // Add the filters for newsletters and subscriber status.
    $data[$table_name][$field_storage->getName() . '_target_id']['filter']['id'] = 'in_operator';
    $data[$table_name][$field_storage->getName() . '_target_id']['filter']['options callback'] = 'simplenews_newsletter_list';
    if ($field_storage->getType() == 'simplenews_subscription') {
      $data[$table_name][$field_storage->getName() . '_status']['filter']['id'] = 'in_operator';
      $data[$table_name][$field_storage->getName() . '_status']['filter']['options callback'] = 'simplenews_subscriber_status_list';
    }
  }
  return $data;
}

/**
 * Implements hook_views_data().
 */
function simplenews_views_data() {

  $data['node']['send_status'] = array(
    'real field' => 'nid',
    'field' => array(
      'title' => t('Send status'),
      'help' => t('Send status of the newsletter.'),
      'id' => 'simplenews_send_status',
    ),
    'group' => t('Content'),
  );

  /* ------------ Definitions for Simplenews mailspool ---------------------- */

  $data['simplenews_mail_spool']['table'] = array(
    'base' => array(
      'field' => 'msid',
      'title' => t('Simplenews mailspool'),
      'help' => t('Spool for temporary storage of newsletter emails.'),
      'weight' => 10,
      'database' => 'default',
    ),
    'group' => t('Simplenews spool'),
  );

  $data['simplenews_mail_spool']['msid'] = array(
    'title' => t('Ms ID'),
    'help' => t('The primary identifier for a mail spool record.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['simplenews_mail_spool']['mail'] = array(
    'title' => t('Subscriber'),
    'help' => t('The formatted email address of mail message recipient.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['simplenews_mail_spool']['entity_id'] = array(
    'title' => t('Node ID'),
    'help' => t('The {node}.nid of this newsletter.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'relationship' => array(
      'id' => 'standard',
      'base' => 'node',
      'base field' => 'nid',
      'label' => t('Node'),
    ),
  );

  $data['simplenews_mail_spool']['newsletter_id'] = array(
    'title' => t('Newsletter ID'),
    'help' => t('The newsletter_id this newsletter issue belongs to.'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'relationship' => array(
      'id' => 'standard',
      'base' => 'simplenews_newsletter',
      'base field' => 'newsletter_id',
      'label' => t('Newsletter'),
    ),
  );

  $data['simplenews_mail_spool']['snid'] = array(
    'title' => t('Subscriber ID'),
    'help' => t('The {simplenews_subscriber}.snid foreign key for this spool'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'relationship' => array(
      'id' => 'standard',
      'base' => 'simplenews_subscriber',
      'base field' => 'snid',
      'label' => t('Subscriber'),
    ),
  );

  $data['simplenews_mail_spool']['status'] = array(
    'title' => t('Sent status'),
    'help' => t('The sent status of the email (0 = hold, 1 = pending, 2 = done).'),
    'field' => array(
      'id' => 'numeric',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['simplenews_mail_spool']['error'] = array(
    'title' => t('Error'),
    'help' => t('A boolean indicating whether an error occurred while sending the email.'),
    'field' => array(
      'id' => 'boolean',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'boolean',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['simplenews_mail_spool']['timestamp'] = array(
    'title' => t('Timestamp'),
    'help' => t('The time status was set or changed.'),
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'date',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
  );

  $data['simplenews_mail_spool']['data'] = array(
    'title' => t('Data'),
    'help' => t('A serialized array of name value pairs that are related to the email address.'),
    'field' => array(
      'id' => 'standard',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id' => 'string',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  return $data;
}
