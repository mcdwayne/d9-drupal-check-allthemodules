<?php

namespace Drupal\oh_review\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\oh_regular\OhRegularInterface;
use Drupal\oh_review\Form\OhReviewSidebarForm;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for OH Review routes.
 */
class OhReviewRouteSubscriber extends RouteSubscriberBase {

  /**
   * OH regular service.
   *
   * @var \Drupal\oh_regular\OhRegularInterface
   */
  protected $ohRegular;

  /**
   * Construct OhRegularSubscriber service.
   *
   * @param \Drupal\oh_regular\OhRegularInterface $ohRegular
   *   OH regular service.
   */
  public function __construct(OhRegularInterface $ohRegular) {
    $this->ohRegular = $ohRegular;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    $mapping = $this->ohRegular->getAllMapping();
    $entityTypes = array_keys($mapping);
    foreach ($entityTypes as $entityType) {
      $id = sprintf('entity.%s.oh_review', $entityType);
      $routeName = sprintf('/%s/{%s}/opening-hours-review', $entityType, $entityType);
      $route = new Route(
        $routeName,
        [
          '_title' => 'Opening Hours',
          '_form' => OhReviewSidebarForm::class,
        ],
        [
          '_permission' => 'access oh_review',
          '_is_oh_bundle' => $entityType,
        ],
        [
          'parameters' => [
            $entityType => [
              'type' => 'entity:' . $entityType,
            ],
          ],
        ]
      );
      $collection->add($id, $route);
    }
  }

}
