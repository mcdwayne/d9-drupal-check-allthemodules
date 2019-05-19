<?php

namespace Drupal\webform_scheduled_tasks;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * The scheduled task route provider.
 */
class ScheduledTaskRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    return $this->upcastWebformParam(parent::getAddFormRoute($entity_type));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditFormRoute(EntityTypeInterface $entity_type) {
    return $this->upcastWebformParam(parent::getEditFormRoute($entity_type));
  }

  /**
   * Upcast a 'webform' route param.
   */
  protected function upcastWebformParam(Route $route) {
    $route->setOption('parameters', [
      'webform' => [
        'type' => 'entity:webform',
      ],
    ]);
    return $route;
  }

}
