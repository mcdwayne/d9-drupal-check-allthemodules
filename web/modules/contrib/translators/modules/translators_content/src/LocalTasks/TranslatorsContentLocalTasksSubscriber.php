<?php

namespace Drupal\translators_content\LocalTasks;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class TranslatorsContentLocalTasksSubscriber.
 *
 * @package Drupal\translators_content\LocalTasks
 */
class TranslatorsContentLocalTasksSubscriber extends RouteSubscriberBase {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * TranslatorsContentLocalTasksSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $manager) {
    $this->entityTypeManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $entity_types = array_keys($this->entityTypeManager->getDefinitions());

    foreach ($entity_types as $entity_type) {
      $route = $collection->get("entity.$entity_type.edit_form");
      if (!empty($route)) {
        $route->addRequirements([
          '_access_checks' => 'content_translation.manage_access',
        ]);
      }
    }
  }

}
