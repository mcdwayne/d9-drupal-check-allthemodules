<?php

/**
 * @file
 * Provide views data and handlers for comment.module.
 *
 * @ingroup views_module_handlers
 */

/**
 * Implements hook_views_data().
 */
function docker_views_data() {

  // Define the base group of this table. Fields that don't have a group defined
  // will go into this field by default.
  $data['docker_host']['table']['group']  = t('Docker host');

  $data['docker_host']['table']['base'] = array(
    'field' => 'dhid',
    'title' => t('Docker host'),
    'help' => t("Docker hosts run Docker."),
    'access query tag' => 'comment_access',
  );
  $data['docker_host']['table']['entity type'] = 'docker_host';
  $data['docker_host']['table']['wizard_id'] = 'docker_host';

  $data['docker_host']['title'] = array(
    'title' => t('Title'),
    'help' => t('The title of the docker host.'),
    'field' => array(
      'id' => 'docker_host',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['docker_host']['dhid'] = array(
    'title' => t('ID'),
    'help' => t('The docker host ID of the field'),
    'field' => array(
      'id' => 'docker_host',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
  );

  $data['docker_host']['name'] = array(
    'title' => t('Host'),
    'help' => t("The name of the docker host."),
    'field' => array(
      'id' => 'name',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['docker_host']['host'] = array(
    'title' => t('Host'),
    'help' => t("The hostname or IP of the docker host."),
    'field' => array(
      'id' => 'host',
    ),
    'filter' => array(
      'id' => 'string',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'string',
    ),
  );

  $data['docker_host']['port'] = array(
    'title' => t("Port"),
    'help' => t("The port of the docker host."),
    'field' => array(
      'id' => 'url',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
  );

  $data['docker_host']['created'] = array(
    'title' => t('Created date'),
    'help' => t('Date and time of when the docker host was created.'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  $data['docker_host']['changed'] = array(
    'title' => t('Updated date'),
    'help' => t('Date and time of when the docker host was last updated.'),
    'field' => array(
      'id' => 'date',
    ),
    'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
  );

  $data['docker_host']['status'] = array(
    'title' => t('Active status'),
    'help' => t('Whether the docker host is active.'),
    'field' => array(
      'id' => 'boolean',
      'output formats' => array(
        'active-not-active' => array(t('Active'), t('Not Active')),
      ),
    ),
    'filter' => array(
      'id' => 'boolean',
      'label' => t('Active status'),
      'type' => 'yes-no',
    ),
    'sort' => array(
      'id' => 'standard',
    ),
  );

  $data['docker_host']['uid'] = array(
    'title' => t('Author uid'),
    'help' => t('If you need more fields than the uid add the docker host: author relationship'),
    'relationship' => array(
      'title' => t('Author'),
      'help' => t("The User ID of the docker host's author."),
      'base' => 'users',
      'base field' => 'uid',
      'id' => 'standard',
      'label' => t('author'),
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),
    'field' => array(
      'id' => 'user',
    ),
  );

  return $data;
}