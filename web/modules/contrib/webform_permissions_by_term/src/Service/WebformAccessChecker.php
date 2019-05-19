<?php

namespace Drupal\webform_permissions_by_term\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\webform_permissions_by_term\Event\EntityFieldValueAccessDeniedEvent;
use Drupal\permissions_by_term\Service\AccessCheck;
use Drupal\Component\EventDispatcher\ContainerAwareEventDispatcher;

/**
 * Class WebformAccessChecker.
 *
 * @package Drupal\webform_permissions_by_term\Service
 */
class WebformAccessChecker extends AccessCheck implements WebformAccessCheckerInterface {

  /**
   * The entity field value access denied event.
   *
   * @var \Drupal\webform_permissions_by_term\Event\EntityFieldValueAccessDeniedEvent
   */
  private $event;

  /**
   * WebformAccessChecker constructor.
   *
   * We override the constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(Connection $database, ContainerAwareEventDispatcher $event_dispatcher) {
    parent::__construct($database, $event_dispatcher);
    $this->event = new EntityFieldValueAccessDeniedEvent();
  }

  /**
   * {@inheritdoc}
   */
  public function isWebformAccessAllowed(ContentEntityInterface $entity, $uid = FALSE) {
    if ($entity->getEntityTypeId() == 'webform_submission') {
      $permissions_by_term = NULL;
      $permissions_by_term = $entity->getData(WEBFORM_SECURITY_ELEMENT_NAME);

      // TODO Extend this so that user can select multiple terms.
      // currently it will only work if the single term is selected.
      if ($permissions_by_term != NULL) {
        if (!$this->isAccessAllowedByDatabase($permissions_by_term, $uid)) {
          // Return that the user is not allowed to access this entity.
          return FALSE;
        }
        return TRUE;
      }

    }
  }

}
