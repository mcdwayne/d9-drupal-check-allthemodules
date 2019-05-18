<?php

namespace Drupal\digitallocker_issuer\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides the digital locker configuration routes for config entities.
 */
class EntityTypeDigitalLockerRouteProvider implements EntityRouteProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();

    // This is the node type entity type.
    if ($entity_type->hasLinkTemplate('digitallocker-form')) {

      $collection->add("entity.{$entity_type->id()}.digitallocker",
        (new Route($entity_type->getLinkTemplate('digitallocker-form')))
          ->setDefaults([
            '_entity_form' => "{$entity_type->id()}.digitallocker",
            '_title' => 'Digital Locker Settings',
          ])
          ->setRequirement('_permission', 'administer content types')
          ->setOption('parameters', [
            $entity_type->id() => ['type' => 'entity:' . $entity_type->id()],
          ]));
    }

    // This is the node entity type.
    if ($entity_type->hasLinkTemplate('download-pdf')) {

      $collection->add("entity.{$entity_type->id()}.digitallocker",
        (new Route($entity_type->getLinkTemplate('download-pdf')))
          ->setDefaults([
            '_controller' => "Drupal\digitallocker_issuer\Controller\DownloadPdfController::downloadPdf",
            '_title' => 'Download PDF',
          ])
          ->setRequirement('_permission', 'access content')
          ->setOption('parameters', [
            $entity_type->id() => ['type' => 'entity:' . $entity_type->id()],
          ]));
    }

    return $collection;
  }

}
