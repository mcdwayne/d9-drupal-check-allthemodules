<?php

namespace Drupal\entity_dispatcher;

/**
 * Class EntityDispatcherEvents
 * @package Drupal\entity_dispatcher
 */
final class EntityDispatcherEvents {

  const ENTITY_INSERT = 'entity_dispatcher.insert';
  const ENTITY_UPDATE = 'entity_dispatcher.update';
  const ENTITY_DELETE = 'entity_dispatcher.delete';
  const ENTITY_PRE_SAVE = 'entity_dispatcher.presave';
  const ENTITY_VIEW = 'entity_dispatcher.view';
  const ENTITY_ACCESS = 'entity_dispatcher.access';
  const ENTITY_CREATE = 'entity_dispatcher.create';
  const ENTITY_LOAD = 'entity_dispatcher.load';

}