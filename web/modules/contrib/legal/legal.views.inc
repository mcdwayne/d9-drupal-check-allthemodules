<?php

/**
 * @file
 * Views handler and plugin definitions for the legal module.
 */

/**
 * Implements hook_views_data().
 */
function legal_views_data() {
  $data                                     = array();
  $data['legal_accepted']['table']['group'] = t('Legal');

  $data['legal_accepted']['table']['join']['users'] = array(
    'left_field' => 'uid',
    'field'      => 'uid',
  );

  $data['users']['legal_accepted_rel'] = array(
    'real field'   => 'uid',
    'title'        => t('accepted user'),
    'group'        => t('Legal'),
    'help'         => t('An accepted user'),
    'relationship' => array(
      'id'                 => 'standard',
      'base'               => 'legal_accepted',
      'base field'         => 'uid',
      'label'              => t('Legal'),
      'relationship field' => 'uid',
    ),
  );

  $data['legal_accepted']['legal_conditions_rel'] = array(
    'group'        => t('Legal'),
    'title'        => t('Legal Conditions relationship'),
    'help'         => t('Create a relationship to a legal conditions and legal accepted.'),
    'relationship' => array(
      'id'                 => 'standard',
      'base'               => 'legal_conditions',
      'base field'         => 'tc_id',
      'label'              => t('Legal'),
      'relationship field' => 'tc_id',
    ),
  );

  $data['legal_accepted']['version'] = array(
    'title'    => t('Version'),
    'help'     => t('Version ID accepted.'),
    'field'    => array(
      'id'             => 'standard',
      'click sortable' => TRUE,
    ),
    'filter'   => array(
      'id'          => 'numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort'     => array(
      'id' => 'standard',
    ),
  );

  $data['legal_accepted']['revision'] = array(
    'title'    => t('Revision'),
    'help'     => t('Revision ID accepted.'),
    'field'    => array(
      'id'             => 'standard',
      'click sortable' => TRUE,
    ),
    'filter'   => array(
      'id'          => 'numeric',
      'allow empty' => TRUE,
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'sort'     => array(
      'id' => 'standard',
    ),
  );

  if (\Drupal::moduleHandler()->moduleExists('locale')) {
    $data['legal_accepted']['language'] = array(
      'title'    => t('Language'),
      'help'     => t('Language of T&C accepted by user.'),
      'field'    => array(
        'id'             => 'language',
        'click sortable' => TRUE,
      ),
      'filter'   => array(
        'id'          => 'language',
        'allow empty' => TRUE,
      ),
      'argument' => array(
        'id' => 'language',
      ),
      'sort'     => array(
        'id' => 'standard',
      ),
    );
  }

  $data['legal_accepted']['uid'] = array(
    'title'        => t('User ID'),
    'help'         => t('User ID of user that accepted.'),
    'field'        => array(
      'id'             => 'user_data',
      'click sortable' => TRUE,
    ),
    'filter'       => array(
      'id'          => 'user_name',
      'allow empty' => TRUE,
    ),
    'argument'     => array(
      'id' => 'user_uid',
    ),
    'sort'         => array(
      'id' => 'standard',
    ),
    'relationship' => array(
      'base'       => 'users',
      'base_field' => 'uid',
      'id'         => 'standard',
      'label'      => t('Acceptances to user relationship'),
    ),
  );

  $data['legal_accepted']['accepted'] = array(
    'title'  => t('Time Accepted'),
    'help'   => t('Time and date user accepted T&Cs.'),
    'field'  => array(
      'id'             => 'date',
      'click sortable' => TRUE,
    ),
    'filter' => array(
      'id'          => 'date',
      'allow empty' => TRUE,
    ),
    'sort'   => array(
      'id' => 'date',
    ),
  );

  $data['legal_conditions'] = array(
    'version'    => array(
      'title'    => t('Version'),
      'help'     => t('Version of T&Cs.'),
      'field'    => array(
        'id'             => 'standard',
        'click sortable' => TRUE,
      ),
      'filter'   => array(
        'id'          => 'numeric',
        'allow empty' => TRUE,
      ),
      'argument' => array(
        'id' => 'numeric',
      ),
      'sort'     => array(
        'id' => 'standard',
      ),
    ),
    'revision'   => array(
      'title'    => t('Revision'),
      'help'     => t('Revision of T&Cs.'),
      'field'    => array(
        'id'             => 'standard',
        'click sortable' => TRUE,
      ),
      'filter'   => array(
        'id'          => 'numeric',
        'allow empty' => TRUE,
      ),
      'argument' => array(
        'id' => 'numeric',
      ),
      'sort'     => array(
        'id' => 'standard',
      ),
    ),
    'conditions' => array(
      'title'    => t('Terms & Conditions'),
      'help'     => t('Text of T&Cs.'),
      'field'    => array(
        'id'             => 'standard',
        'click sortable' => TRUE,
      ),
      'filter'   => array(
        'id'          => 'string',
        'allow empty' => TRUE,
      ),
      'argument' => array(
        'id' => 'string',
      ),
      'sort'     => array(
        'id' => 'standard',
      ),
    ),
    'date'       => array(
      'title'  => t('Date Added'),
      'help'   => t('Date T&Cs where added'),
      'field'  => array(
        'id' => 'date',
      ),
      'filter' => array(
        'id' => 'date',
      ),
      'sort'   => array(
        'id' => 'date',
      ),
    ),
    'extras'     => array(
      'title'    => t('Additional Checkboxes'),
      'help'     => t('Additional terms of T&Cs'),
      'field'    => array(
        'id' => 'unserialized_list',
      ),
      'filter'   => array(
        'id' => 'string',
      ),
      'argument' => array(
        'id' => 'string',
      ),
      'sort'     => array(
        'id' => 'standard',
      ),
    ),
    'changes'    => array(
      'title'    => t('Changes'),
      'help'     => t('Explanation of changes to T&Cs'),
      'field'    => array(
        'id'             => 'exploded_list',
        'click sortable' => TRUE,
      ),
      'filter'   => array(
        'id'          => 'string',
        'allow empty' => TRUE,
      ),
      'argument' => array(
        'id' => 'string',
      ),
      'sort'     => array(
        'id' => 'standard',
      ),
    ),
    'table'      => array(
      'group' => t('Legal'),
      'base'  => array(
        'field'  => 'tc_id',
        'title'  => t('Legal terms and conditions'),
        'help'   => 'Versions of the terms and conditions text',
        'weight' => 10,
      ),
    ),
  );

  if (\Drupal::moduleHandler()->moduleExists('locale')) {

    $data['legal_conditions']['language'] = array(
      'title'    => t('Language'),
      'help'     => t('Language of T&Cs.'),
      'field'    => array(
        'id'             => 'language',
        'click sortable' => TRUE,
      ),
      'filter'   => array(
        'id'          => 'language',
        'allow empty' => TRUE,
      ),
      'argument' => array(
        'id' => 'language',
      ),
      'sort'     => array(
        'id' => 'standard',
      ),
    );
  }

  return $data;
}


