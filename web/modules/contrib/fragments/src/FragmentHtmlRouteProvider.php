<?php

namespace Drupal\fragments;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for Fragment entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class FragmentHtmlRouteProvider extends AdminHtmlRouteProvider {

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
      $collection->add("entity.{$entity_type_id}.revision_revert", $revert_route);
    }

    if ($delete_route = $this->getRevisionDeleteRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision_delete", $delete_route);
    }

    if ($translation_route = $this->getRevisionTranslationRevertRoute($entity_type)) {
      $collection->add("{$entity_type_id}.revision_revert_translation_confirm", $translation_route);
    }

    return $collection;
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
    if (!$entity_type->hasLinkTemplate('version-history')) {
      return NULL;
    }

    $route = new Route($entity_type->getLinkTemplate('version-history'));
    $route
      ->setDefaults([
        '_title' => "{$entity_type->getLabel()} revisions",
        '_controller' => '\Drupal\fragments\Controller\FragmentController::revisionOverview',
      ])
      ->setRequirement('_permission', 'access fragment revisions')
      ->setOption('_admin_route', TRUE);

    return $route;
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
    if (!$entity_type->hasLinkTemplate('revision')) {
      return NULL;
    }

    $route = new Route($entity_type->getLinkTemplate('revision'));
    $route
      ->setDefaults([
        '_controller' => '\Drupal\fragments\Controller\FragmentController::revisionShow',
        '_title_callback' => '\Drupal\fragments\Controller\FragmentController::revisionPageTitle',
      ])
      ->setRequirement('_permission', 'access fragment revisions')
      ->setOption('_admin_route', TRUE);

    return $route;
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
    if (!$entity_type->hasLinkTemplate('revision_revert')) {
      return NULL;
    }

    $route = new Route($entity_type->getLinkTemplate('revision_revert'));
    $route
      ->setDefaults([
        '_form' => '\Drupal\fragments\Form\FragmentRevisionRevertForm',
        '_title' => 'Revert to earlier revision',
      ])
      ->setRequirement('_permission', 'revert all fragment revisions')
      ->setOption('_admin_route', TRUE);

    return $route;
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
    if (!$entity_type->hasLinkTemplate('revision_delete')) {
      return NULL;
    }

    $route = new Route($entity_type->getLinkTemplate('revision_delete'));
    $route
      ->setDefaults([
        '_form' => '\Drupal\fragments\Form\FragmentRevisionDeleteForm',
        '_title' => 'Delete earlier revision',
      ])
      ->setRequirement('_permission', 'delete all fragment revisions')
      ->setOption('_admin_route', TRUE);

    return $route;
  }

  /**
   * Gets the revision translation revert route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionTranslationRevertRoute(EntityTypeInterface $entity_type) {
    if (!$entity_type->hasLinkTemplate('translation_revert')) {
      return NULL;
    }

    $route = new Route($entity_type->getLinkTemplate('translation_revert'));
    $route
      ->setDefaults([
        '_form' => '\Drupal\fragments\Form\FragmentRevisionRevertTranslationForm',
        '_title' => 'Revert to earlier revision of a translation',
      ])
      ->setRequirement('_permission', 'revert all fragment revisions')
      ->setOption('_admin_route', TRUE);

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type) {
    $route = parent::getCanonicalRoute($entity_type);

    // Override default access for canonical route. Regular users should not be
    // able to view Fragments on their own page.
    $entity_type_id = $entity_type->id();
    $route->setRequirement('_entity_access', "{$entity_type_id}.view individual");

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    $route = parent::getCollectionRoute($entity_type);
    $route->setRequirement('_permission', 'access fragments overview');

    return $route;
  }

}
