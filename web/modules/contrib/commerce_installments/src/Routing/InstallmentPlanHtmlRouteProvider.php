<?php

namespace Drupal\commerce_installments\Routing;

use Drupal\commerce_installments\Controller\InstallmentPlanEntityController;
use Drupal\commerce_installments\Controller\InstallmentPlanController;
use Drupal\commerce_installments\Form\InstallmentPlanRevisionDeleteForm;
use Drupal\commerce_installments\Form\InstallmentPlanRevisionRevertForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Installment Plan entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class InstallmentPlanHtmlRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($history_route = $this->getHistoryRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.version_history", $history_route);
    }

    if ($revision_route = $this->getRevisionRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision", $revision_route);
    }

    if ($revert_route = $this->getRevisionRevertRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision_revert_form", $revert_route);
    }

    if ($delete_route = $this->getRevisionDeleteRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision_delete_form", $delete_route);
    }

    foreach ($collection->all() as $route) {
      $parameters = $route->getOption('parameters') ? $route->getOption('parameters') : [];
      $parameters = $parameters + [
        'commerce_order' => [
          'type' => 'entity:commerce_order',
        ],
        'installment_plan' => [
          'type' => 'entity:installment_plan',
        ],
      ];
      $route->setOption('parameters', $parameters);
    }

    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddPageRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getAddPageRoute($entity_type)) {
      return $route->setDefault('_controller', InstallmentPlanEntityController::class . '::addPage');
    }
  }

  /**
   * Gets the version history route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getHistoryRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('version-history')) {
      $route = new Route($entity_type->getLinkTemplate('version-history'));
      $entity_type_id = $entity_type->id();
      $route
        ->setDefaults([
          '_title' => "{$entity_type->getLabel()} revisions",
          '_controller' => InstallmentPlanController::class . '::revisionOverviewController',
        ])
        ->setRequirement('_permission', 'access installment plan revisions')
        ->setOption('entity_type_id', $entity_type_id);
      return $route;
    }
  }

  /**
   * Gets the revision route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('revision')) {
      $route = new Route($entity_type->getLinkTemplate('revision'));
      $route
        ->setDefaults([
          '_controller' => InstallmentPlanController::class . '::revisionShow',
          '_title_callback' => InstallmentPlanController::class . '::revisionPageTitle',
        ])
        ->setRequirement('_permission', 'access installment plan revisions')
        ->setOption('parameters', [
          $entity_type->id() . '_revision' => [
            'type' => 'entity_revision:' . $entity_type->id(),
          ],
        ]);

      return $route;
    }
  }

  /**
   * Gets the revision revert route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionRevertRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('revision-revert-form')) {
      $route = new Route($entity_type->getLinkTemplate('revision-revert-form'));
      $route
        ->setDefaults([
          '_form' => InstallmentPlanRevisionRevertForm::class,
          '_title' => 'Revert to earlier revision',
        ])
        ->setRequirement('_permission', 'revert all installment plan revisions')
        ->setOption('parameters', [
          $entity_type->id() . '_revision' => [
            'type' => 'entity_revision:' . $entity_type->id(),
          ],
        ]);

      return $route;
    }
  }

  /**
   * Gets the revision delete route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionDeleteRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('revision-delete-form')) {
      $route = new Route($entity_type->getLinkTemplate('revision-delete-form'));
      $route
        ->setDefaults([
          '_form' => InstallmentPlanRevisionDeleteForm::class,
          '_title' => 'Delete earlier revision',
        ])
        ->setRequirement('_permission', 'delete all installment plan revisions')
        ->setOption('parameters', [
          $entity_type->id() . '_revision' => [
            'type' => 'entity_revision:' . $entity_type->id(),
          ],
        ]);

      return $route;
    }
  }

}
