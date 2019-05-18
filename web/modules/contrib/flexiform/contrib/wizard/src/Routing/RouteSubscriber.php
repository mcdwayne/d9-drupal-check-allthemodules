<?php

namespace Drupal\flexiform_wizard\Routing;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * Subscriber for wizard routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    foreach ($this->entityTypeManager->getStorage('flexiform_wizard')->loadMultiple() as $wizard_id => $wizard) {
      $options = [];
      foreach ($wizard->get('parameters') as $param_name => $param_info) {
        $options['parameters'][$param_name] = [
          'type' => 'entity:' . $param_info['entity_type'],
        ];
      }
      $options['parameters']['wizard'] = [
        'type' => 'entity:flexiform_wizard',
      ];

      $defaults = [
        '_wizard' => '\Drupal\flexiform_wizard\Wizard\DefaultWizard',
        '_title' => 'Wizard',
        'wizard' => $wizard_id,
      ];

      $route = new Route(
        $wizard->get('path'),
        $defaults,
        [
          '_access' => 'TRUE',
        ],
        $options
      );
      $collection->add("flexiform_wizard.{$wizard_id}", $route);
      $route = new Route(
        $wizard->get('path') . '/{step}',
        $defaults,
        [
          '_access' => 'TRUE',
        ],
        $options
      );
      $collection->add("flexiform_wizard.{$wizard_id}.step", $route);
    }
  }

}
