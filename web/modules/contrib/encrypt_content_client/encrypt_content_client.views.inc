<?php

/**
 * Implements hook_views_data_alter().
 */
function encrypt_content_client_views_data_alter(array &$data) {
  $data['node']['client_encryption_checker'] = array(
    'title' => t('Checks if a node is encrypted with client encryption.'),
    'field' => array(
      'title' => t('Client Encryption'),
      'help' => t('Checks if a node is encrypted with client encryption.'),
      'id' => 'client_encryption_checker',
    ),
  );
}