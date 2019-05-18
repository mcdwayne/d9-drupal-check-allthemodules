<?php

/**
 * @file
 * Provide views data and handlers for pinger.module.
 *
 * @ingroup views_module_handlers
 */

use Drupal\Core\Entity\DatabaseStorageController;

/**
 * Implements hook_views_data().
 */
function pinger_views_data() {
  $data['pinger_sites']['table']['group'] = t('Pinger Sites');
  $data['pinger_sites']['table']['base'] = array(
    'field' => 'id',
    'title' => t('Pinger Site'),
    'help' => t('Sites.'),
  );
  $data['pinger_sites']['id'] = array(
    'title' => t('Pinger Site ID.'),
    'help' => t('The unique ID of the site.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'site_id',
      'name field' => 'title',
      'numeric' => TRUE,
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['pinger_sites']['url'] = array(
    'title' => t('URL of site'),
    'help' => t('The url of the site.'),
    'field' => array(
      'id' => 'url',
    ),
    'argument' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'string',
    ),
  );

  $data['pinger_responses']['table']['group'] = t('Pinger Responses');
  $data['pinger_responses']['table']['base'] = array(
    'field' => 'id',
    'title' => t('Pinger Responses'),
    'help' => t('Sites.'),
  );

  $data['pinger_responses']['id'] = array(
    'title' => t('ID'),
    'help' => t('Pinger Site ID.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['pinger_responses']['site_id'] = array(
    'title' => t('Pinger Site ID'),
    'help' => t('The local pinger site ID.'),
    'field' => array(
      'id' => 'numeric',
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
  $data['pinger_responses']['response_code'] = array(
    'title' => t('Response Code'),
    'help' => t('Http response code'),
    'field' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
  );
  $data['pinger_responses']['response_time'] = array(
    'title' => t('Response Time'),
    'help' => t('Duration of response'),
    'field' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
  );
  $data['pinger_responses']['timestamp'] = array(
    'title' => t('Response Timestamp'),
    'help' => t('Timestamp of http response'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'argument' => array(
      'id' => 'date',
    ),
  );

  return $data;
}
