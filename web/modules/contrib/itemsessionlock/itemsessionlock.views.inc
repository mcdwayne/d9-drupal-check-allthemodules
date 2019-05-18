<?php

/**
 * @file
 * Expose locks data to Views.
 */

/**
 * Implements hook_views_data().
 */
function itemsessionlock_views_data() {

  $data['itemsessionlock']['table']['group'] = t('Item session locks');

  $data['itemsessionlock']['table']['base'] = array(
    // Totally unsure how this can work without defining the primary key.
//  'field' => array('iid', 'type'),
    'title' => t('Item session locks'),
  );
  // Join with users.
  $data['itemsessionlock']['table']['join'] = array(
    'users' => array(
      'left_field' => 'uid',
      'field' => 'uid',
    ),
  );
  // User field.
  $data['itemsessionlock']['uid'] = array(
    'title' => t('User'),
    'help' => t('User owning the lock.'),
    'field' => array(
      'id' => 'numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'search' => array(
      'id' => 'standard',
    ),
    'relationship' => array(
      'base' => 'users',
      'base field' => 'uid',
      'id' => 'standard',
      'label' => t('Lock owner'),
      'title' => t('Lock owner'),
      'help' => t('User owning the lock'),
    ),
  );
  // Most of our fields are text.
  $text_fields = array(
    'sid' => t('Session sid'),
    'ssid' => t('Secure session sid'),
    'type' => t('Lock item type'),
    'iid' => t('Lock item identifier'),
  );
  foreach ($text_fields as $name => $label) {
    $data['itemsessionlock'][$name] = array(
      'title' => $label,
      'field' => array(
        'id' => 'standard',
        'click sortable' => TRUE,
      ),
      'sort' => array(
        'handler' => 'standard',
      ),
      'filter' => array(
        'handler' => 'string',
      ),
      'argument' => array(
        'handler' => 'string',
      ),
    );
  }

  // Timestamp field.
  $data['itemsessionlock']['timestamp'] = array(
    'title' => t('Timestamp'),
    'help' => t('Time when the lock was set.'),
    'field' => array(
      'id' => 'date',
      'click sortable' => TRUE,
    ),
    'sort' => array(
      'handler' => 'standard',
    ),
    'filter' => array(
      'handler' => 'date',
    ),
  );

  // Break link.
  $data['itemsessionlock']['break_link'] = array(
    'title' => t('Break link'),
    'field' => array(
      'id' => 'itemsessionlock_break_link',
      'real field' => 'iid',
    ),
  );

  return $data;
}
