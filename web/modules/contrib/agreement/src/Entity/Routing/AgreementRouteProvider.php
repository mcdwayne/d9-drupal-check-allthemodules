<?php

namespace Drupal\agreement\Entity\Routing;

use Drupal\agreement\Entity\Agreement;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Routing\EntityRouteProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provide routes for each agreement entity.
 */
class AgreementRouteProvider implements EntityRouteProviderInterface, EntityHandlerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Initialize method.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = new RouteCollection();

    // Find every agreement and ensure that routes are created for each path.
    $agreements = $this->entityTypeManager
      ->getStorage('agreement')
      ->loadMultiple();

    if (!empty($agreements)) {
      foreach ($agreements as $id => $agreement) {
        $collection->add("agreement.$id", $this->getCanonicalRouteForEntity($agreement));
      }
    }

    return $collection;
  }

  /**
   * Get the route information from the agreement entity.
   *
   * @param \Drupal\agreement\Entity\Agreement $agreement
   *   The agreement entity.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   A route object.
   */
  protected function getCanonicalRouteForEntity(Agreement $agreement) {
    $route = new Route($agreement->get('path'));
    $route
      ->addDefaults([
        '_form' => '\Drupal\agreement\Form\AgreementForm',
        '_title_callback' => '\Drupal\agreement\Form\AgreementForm::title',
        'agreement' => $agreement->id(),
      ])
      ->setRequirements(['_permission' => 'access content'])
      ->setOption(
        'parameters',
        [
          'agreement' => ['type' => 'entity:agreement'],
        ]
      );

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

}
